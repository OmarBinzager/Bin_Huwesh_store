"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import {
  getMapPlaceDetails,
  reverseGeocodeLocation,
  searchMapPlaces,
  type MapPlaceSuggestion,
  type StoreConfig,
} from "@/lib/customer-api";
import { useI18n } from "@/lib/i18n/context";

export type MapLocationValue = {
  latitude: string;
  longitude: string;
  address?: string;
};

type MapLocationPickerProps = {
  latitude?: string;
  longitude?: string;
  config?: StoreConfig | null;
  onChange: (value: MapLocationValue) => void;
};

type LeafletLike = {
  map: (el: HTMLElement, opts: { center: [number, number]; zoom: number }) => LeafletMapLike;
  tileLayer: (url: string, opts: { attribution: string; maxZoom: number }) => { addTo: (m: LeafletMapLike) => void };
  marker: (pos: [number, number], opts: { draggable: boolean }) => LeafletMarkerLike;
};

type LeafletMapLike = {
  setView: (pos: [number, number], zoom?: number) => void;
  on: (event: string, cb: (e: { latlng: { lat: number; lng: number } }) => void) => void;
  remove: () => void;
};

type LeafletMarkerLike = {
  addTo: (m: LeafletMapLike) => LeafletMarkerLike;
  setLatLng: (pos: [number, number]) => void;
  getLatLng: () => { lat: number; lng: number };
  on: (event: string, cb: () => void) => void;
};

type GoogleMapsLike = {
  maps: {
    Map: new (
      el: HTMLElement,
      opts: { center: { lat: number; lng: number }; zoom: number; mapTypeControl: boolean; streetViewControl: boolean },
    ) => {
      panTo: (pos: { lat: number; lng: number }) => void;
      setZoom: (z: number) => void;
      addListener: (event: string, cb: (e: { latLng?: { lat: () => number; lng: () => number } }) => void) => void;
    };
    Marker: new (opts: {
      position: { lat: number; lng: number };
      map: unknown;
      draggable: boolean;
    }) => {
      setPosition: (pos: { lat: number; lng: number }) => void;
      getPosition: () => { lat: () => number; lng: () => number } | null;
      addListener: (event: string, cb: () => void) => void;
    };
  };
};

type MapWindow = Window & {
  google?: GoogleMapsLike;
  L?: LeafletLike;
};

function getMapWindow(): MapWindow | undefined {
  return typeof window !== "undefined" ? (window as MapWindow) : undefined;
}

const DEFAULT_LAT = 14.5425;
const DEFAULT_LNG = 49.1242;

function parseCoord(value: unknown): number | null {
  if (typeof value === "number" && Number.isFinite(value)) return value;
  if (typeof value === "string" && value.trim() !== "") {
    const num = Number(value);
    if (Number.isFinite(num)) return num;
  }
  return null;
}

function loadGoogleMapsScript(apiKey: string, lang: string): Promise<boolean> {
  const win = getMapWindow();
  if (!win) return Promise.resolve(false);
  if (win.google?.maps) return Promise.resolve(true);

  const existing = document.querySelector<HTMLScriptElement>('script[data-map-sdk="google"]');
  if (existing) {
    return new Promise((resolve) => {
      existing.addEventListener("load", () => resolve(Boolean(win.google?.maps)), { once: true });
      existing.addEventListener("error", () => resolve(false), { once: true });
    });
  }

  return new Promise((resolve) => {
    const script = document.createElement("script");
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&language=${encodeURIComponent(lang)}`;
    script.async = true;
    script.defer = true;
    script.dataset.mapSdk = "google";
    script.onload = () => resolve(Boolean(win.google?.maps));
    script.onerror = () => resolve(false);
    document.head.appendChild(script);
  });
}

function loadLeafletAssets(): Promise<boolean> {
  const win = getMapWindow();
  if (!win) return Promise.resolve(false);
  if (win.L) return Promise.resolve(true);

  if (!document.querySelector('link[data-map-sdk="leaflet-css"]')) {
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
    link.dataset.mapSdk = "leaflet-css";
    document.head.appendChild(link);
  }

  const existing = document.querySelector<HTMLScriptElement>('script[data-map-sdk="leaflet-js"]');
  if (existing) {
    return new Promise((resolve) => {
      existing.addEventListener("load", () => resolve(Boolean(win.L)), { once: true });
      existing.addEventListener("error", () => resolve(false), { once: true });
    });
  }

  return new Promise((resolve) => {
    const script = document.createElement("script");
    script.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
    script.async = true;
    script.dataset.mapSdk = "leaflet-js";
    script.onload = () => resolve(Boolean(win.L));
    script.onerror = () => resolve(false);
    document.head.appendChild(script);
  });
}

export default function MapLocationPicker({
  latitude,
  longitude,
  config,
  onChange,
}: MapLocationPickerProps) {
  const { locale, t } = useI18n();
  const containerRef = useRef<HTMLDivElement>(null);

  const initialLat =
    parseCoord(latitude) ??
    parseCoord(config?.ecommerce_location_coverage?.latitude) ??
    parseCoord(config?.branches?.[0]?.latitude) ??
    DEFAULT_LAT;
  const initialLng =
    parseCoord(longitude) ??
    parseCoord(config?.ecommerce_location_coverage?.longitude) ??
    parseCoord(config?.branches?.[0]?.longitude) ??
    DEFAULT_LNG;

  const [coords, setCoords] = useState<{ lat: number; lng: number }>({
    lat: initialLat,
    lng: initialLng,
  });
  const [searchQuery, setSearchQuery] = useState("");
  const [suggestions, setSuggestions] = useState<MapPlaceSuggestion[]>([]);
  const [searching, setSearching] = useState(false);
  const [locating, setLocating] = useState(false);
  const [geoError, setGeoError] = useState<string | null>(null);
  const [activeEngine, setActiveEngine] = useState<"google" | "osm" | null>(null);

  const onChangeRef = useRef(onChange);
  useEffect(() => {
    onChangeRef.current = onChange;
  }, [onChange]);

  const googleMapRef = useRef<{
    map: InstanceType<GoogleMapsLike["maps"]["Map"]>;
    marker: InstanceType<GoogleMapsLike["maps"]["Marker"]>;
  } | null>(null);
  const leafletMapRef = useRef<{
    map: LeafletMapLike;
    marker: LeafletMarkerLike;
  } | null>(null);

  const useGoogleMaps = Boolean(
    config?.google_map_status === 1 && config?.map_api_client_key?.trim(),
  );

  const resolveAddressFromCoords = useCallback(
    async (lat: number, lng: number): Promise<string | undefined> => {
      if (config?.google_map_status === 1) {
        const googleAddress = await reverseGeocodeLocation(lat, lng, locale);
        if (googleAddress) return googleAddress;
      }
      try {
        const res = await fetch(
          `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=${locale}`,
        );
        if (!res.ok) return undefined;
        const data = (await res.json()) as { display_name?: string };
        return data.display_name;
      } catch {
        return undefined;
      }
    },
    [config?.google_map_status, locale],
  );

  const commitPosition = useCallback(
    async (lat: number, lng: number, explicitAddress?: string) => {
      const fixedLat = Number(lat.toFixed(6));
      const fixedLng = Number(lng.toFixed(6));
      setCoords({ lat: fixedLat, lng: fixedLng });
      setGeoError(null);

      if (googleMapRef.current) {
        const pos = { lat: fixedLat, lng: fixedLng };
        googleMapRef.current.marker.setPosition(pos);
        googleMapRef.current.map.panTo(pos);
      }
      if (leafletMapRef.current) {
        const pos: [number, number] = [fixedLat, fixedLng];
        leafletMapRef.current.marker.setLatLng(pos);
        leafletMapRef.current.map.setView(pos);
      }

      const resolvedAddress =
        explicitAddress ?? (await resolveAddressFromCoords(fixedLat, fixedLng));
      onChangeRef.current({
        latitude: String(fixedLat),
        longitude: String(fixedLng),
        ...(resolvedAddress ? { address: resolvedAddress } : {}),
      });
    },
    [resolveAddressFromCoords],
  );

  useEffect(() => {
    let cancelled = false;
    const el = containerRef.current;
    if (!el) return;

    const init = async () => {
      const win = getMapWindow();
      if (!win) return;

      if (useGoogleMaps && config?.map_api_client_key) {
        const loaded = await loadGoogleMapsScript(config.map_api_client_key, locale);
        if (cancelled) return;
        if (loaded && win.google?.maps) {
          const pos = { lat: initialLat, lng: initialLng };
          const map = new win.google.maps.Map(el, {
            center: pos,
            zoom: 14,
            mapTypeControl: false,
            streetViewControl: false,
          });
          const marker = new win.google.maps.Marker({
            position: pos,
            map,
            draggable: true,
          });
          map.addListener("click", (e) => {
            if (!e.latLng) return;
            void commitPosition(e.latLng.lat(), e.latLng.lng());
          });
          marker.addListener("dragend", () => {
            const p = marker.getPosition();
            if (!p) return;
            void commitPosition(p.lat(), p.lng());
          });
          googleMapRef.current = { map, marker };
          setActiveEngine("google");
          return;
        }
      }

      const leafletReady = await loadLeafletAssets();
      if (cancelled || !leafletReady || !win.L) return;

      const map = win.L.map(el, {
        center: [initialLat, initialLng],
        zoom: 14,
      });
      win.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap",
        maxZoom: 19,
      }).addTo(map);
      const marker = win.L.marker([initialLat, initialLng], {
        draggable: true,
      }).addTo(map);

      map.on("click", (e) => {
        void commitPosition(e.latlng.lat, e.latlng.lng);
      });
      marker.on("dragend", () => {
        const ll = marker.getLatLng();
        void commitPosition(ll.lat, ll.lng);
      });

      leafletMapRef.current = { map, marker };
      setActiveEngine("osm");
    };

    void init();

    return () => {
      cancelled = true;
      googleMapRef.current = null;
      if (leafletMapRef.current) {
        leafletMapRef.current.map.remove();
        leafletMapRef.current = null;
      }
    };
  }, [useGoogleMaps, config?.map_api_client_key, locale, initialLat, initialLng, commitPosition]);

  const handleSearch = async () => {
    const q = searchQuery.trim();
    if (!q) return;
    setSearching(true);
    setSuggestions([]);

    if (config?.google_map_status === 1) {
      const items = await searchMapPlaces(q, locale);
      if (items.length > 0) {
        setSuggestions(items);
        setSearching(false);
        return;
      }
    }

    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(q)}&accept-language=${locale}`,
      );
      if (res.ok) {
        const list = (await res.json()) as Array<{
          place_id: number;
          display_name: string;
          lat: string;
          lon: string;
        }>;
        setSuggestions(
          list.map((item) => ({
            placeId: `osm:${item.lat},${item.lon}`,
            label: item.display_name,
          })),
        );
      }
    } catch {
      // ignore network failure
    }
    setSearching(false);
  };

  const handleSelectSuggestion = async (suggestion: MapPlaceSuggestion) => {
    setSuggestions([]);
    setSearchQuery(suggestion.label);

    if (suggestion.placeId.startsWith("osm:")) {
      const [latStr, lngStr] = suggestion.placeId.slice(4).split(",");
      const lat = Number(latStr);
      const lng = Number(lngStr);
      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        await commitPosition(lat, lng, suggestion.label);
      }
      return;
    }

    const details = await getMapPlaceDetails(suggestion.placeId, locale);
    if (details) {
      await commitPosition(details.latitude, details.longitude, details.address ?? suggestion.label);
    }
  };

  const handleCurrentLocation = () => {
    if (!navigator.geolocation) {
      setGeoError(t.mapPicker.locationBlocked);
      return;
    }
    setLocating(true);
    setGeoError(null);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setLocating(false);
        void commitPosition(pos.coords.latitude, pos.coords.longitude);
      },
      () => {
        setLocating(false);
        setGeoError(t.mapPicker.locationBlocked);
      },
      { enableHighAccuracy: true, timeout: 10000 },
    );
  };

  return (
    <div className="space-y-3 rounded-2xl border border-line bg-stage/60 p-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="text-xs font-semibold text-ink">{t.mapPicker.title}</span>
          {activeEngine && (
            <span className="rounded-full border border-line bg-card px-2.5 py-0.5 text-[10px] font-medium text-muted">
              {activeEngine === "google" ? t.mapPicker.googleMapBadge : t.mapPicker.osmMapBadge}
            </span>
          )}
        </div>
        <button
          type="button"
          disabled={locating}
          onClick={handleCurrentLocation}
          className="inline-flex h-8 items-center gap-1.5 rounded-full border border-line bg-card px-3 text-xs font-medium text-ink transition hover:border-brand hover:text-brand disabled:opacity-50"
        >
          <span>{locating ? t.mapPicker.locating : t.mapPicker.useCurrentLocation}</span>
        </button>
      </div>

      {/* Search bar */}
      <div className="relative flex gap-2">
        <input
          type="text"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === "Enter") {
              e.preventDefault();
              void handleSearch();
            }
          }}
          placeholder={t.mapPicker.searchPlaceholder}
          className="h-9 flex-1 rounded-xl border border-line bg-background px-3 text-xs text-ink outline-none focus:border-brand"
        />
        <button
          type="button"
          disabled={searching}
          onClick={() => void handleSearch()}
          className="h-9 rounded-xl bg-ink px-4 text-xs font-medium text-on-ink transition hover:bg-brand disabled:opacity-50"
        >
          {t.mapPicker.searchBtn}
        </button>

        {suggestions.length > 0 && (
          <ul className="absolute inset-x-0 top-10 z-20 max-h-48 overflow-y-auto rounded-2xl border border-line bg-card p-1.5 shadow-lg">
            {suggestions.map((s) => (
              <li key={s.placeId}>
                <button
                  type="button"
                  onClick={() => void handleSelectSuggestion(s)}
                  className="w-full rounded-xl px-3 py-2 text-start text-xs text-ink transition hover:bg-stage"
                >
                  {s.label}
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>

      {/* Interactive map container */}
      <div
        ref={containerRef}
        className="h-60 w-full overflow-hidden rounded-2xl border border-line bg-background"
      />

      {geoError && <p className="text-[11px] text-brand">{geoError}</p>}

      <div className="flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted">
        <span>{t.mapPicker.tapHint}</span>
        <span className="nums" dir="ltr">
          {t.mapPicker.selectedCoords} {coords.lat}, {coords.lng}
        </span>
      </div>
    </div>
  );
}
