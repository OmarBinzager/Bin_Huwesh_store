import type { Locale } from "@/lib/i18n/config";
import { API_BASE_URL } from "@/lib/storefront-api";
import { getAuthToken } from "@/lib/auth-api";

export type CurrencyItem = {
  id: number;
  country: string;
  currency_code: string;
  currency_symbol: string;
  symbol_position: "left" | "right" | string;
  decimal_digits: number;
  exchange_rate: number;
  is_base?: number | boolean;
};

export type BranchItem = {
  id: number;
  name: string;
  email?: string;
  longitude?: string;
  latitude?: string;
  address?: string;
  coverage?: number;
  status?: number;
};

export type PaymentGatewayItem = {
  gateway: string;
  gateway_title?: string;
  gateway_image?: string;
};

export type StoreConfig = {
  ecommerce_name?: string;
  currency_symbol?: string;
  currency_symbol_position?: string;
  active_currencies?: CurrencyItem[];
  delivery_charge?: number;
  delivery_management?: {
    status: number;
    min_shipping_charge: number;
    shipping_per_km: number;
  };
  branches?: BranchItem[];
  ecommerce_location_coverage?: {
    latitude?: string | number;
    longitude?: string | number;
    coverage?: number;
  } | null;
  google_map_status?: number;
  map_api_client_key?: string;
  free_delivery_over_amount_status?: number;
  free_delivery_over_amount?: number;
  maximum_amount_for_cod_order_status?: number;
  maximum_amount_for_cod_order?: number;
  cash_on_delivery?: string;
  digital_payment?: string;
  offline_payment?: string;
  active_payment_method_list?: PaymentGatewayItem[];
  guest_checkout?: number;
  whatsapp?: { status?: number; number?: string };
};

export type CustomerAddress = {
  id: number;
  address_type: string;
  contact_person_name: string;
  contact_person_number: string;
  address: string;
  road?: string | null;
  house?: string | null;
  floor?: string | null;
  latitude?: string | null;
  longitude?: string | null;
};

export type TimeSlotItem = {
  id: number;
  start_time: string;
  end_time: string;
  status?: number;
};

export type CouponItem = {
  id: number;
  title?: string;
  code: string;
  discount: number;
  discount_type: string;
  min_purchase?: number;
  max_discount?: number;
  expire_date?: string;
};

export type CustomerOrder = {
  id: number;
  order_amount: number;
  currency_code?: string;
  currency_amount?: number;
  coupon_discount_amount?: number;
  delivery_charge?: number;
  order_status: string;
  payment_status: string;
  payment_method: string;
  order_type?: string;
  delivery_date?: string;
  created_at: string;
  details_count?: number;
  branch?: { id: number; name: string } | null;
};

export type OrderDetailItem = {
  id: number;
  product_id: number;
  quantity: number;
  price: number;
  discount_on_product?: number;
  variation?: string;
  product_details?: {
    id?: number;
    name?: string;
    image?: string[];
  };
};

export type NotificationItem = {
  id: number;
  title: string;
  description: string;
  image?: string | null;
  created_at: string;
};

function authHeaders(locale: Locale, guestId?: string): Record<string, string> {
  const token = getAuthToken();
  return {
    Accept: "application/json",
    "Content-Type": "application/json",
    "X-localization": locale,
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(!token && guestId ? { "guest-id": guestId } : {}),
  };
}

export type DeliveryAreaItem = {
  id: number;
  branch_id?: number;
  area_name: string;
  delivery_charge: number;
};

export type DeliveryChargeSetup = {
  delivery_charge_type?: "fixed" | "area" | "distance" | string;
  fixed_delivery_charge?: number;
  minimum_delivery_charge?: number;
  delivery_charge_per_kilometer?: number;
  minimum_distance_for_free_delivery?: number;
};

export type BranchDeliveryFeeInfo = {
  id: number;
  name?: string;
  latitude?: string | number | null;
  longitude?: string | number | null;
  delivery_charge_setup?: DeliveryChargeSetup | null;
  delivery_charge_by_area?: DeliveryAreaItem[];
};

export async function fetchDeliveryFeeSetup(
  locale: Locale = "ar",
): Promise<BranchDeliveryFeeInfo[]> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/config/delivery-fee`, {
      cache: "no-store",
      headers: { Accept: "application/json", "X-localization": locale },
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? (data as BranchDeliveryFeeInfo[]) : [];
  } catch {
    return [];
  }
}

export async function fetchStoreConfig(locale: Locale = "ar"): Promise<StoreConfig | null> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/config`, {
      cache: "no-store",
      headers: { Accept: "application/json", "X-localization": locale },
    });
    if (!res.ok) return null;
    return (await res.json()) as StoreConfig;
  } catch {
    return null;
  }
}

export async function fetchTimeSlots(locale: Locale = "ar"): Promise<TimeSlotItem[]> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/timeSlot`, {
      headers: { Accept: "application/json", "X-localization": locale },
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : data?.data ?? [];
  } catch {
    return [];
  }
}

export async function fetchCoupons(locale: Locale = "ar"): Promise<CouponItem[]> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/coupon/list`, {
      headers: authHeaders(locale),
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : data?.data ?? [];
  } catch {
    return [];
  }
}

export async function fetchAddresses(locale: Locale = "ar", guestId?: string): Promise<CustomerAddress[]> {
  try {
    const qs = guestId ? `?guest_id=${encodeURIComponent(guestId)}` : "";
    const res = await fetch(`${API_BASE_URL}/api/v1/customer/address/list${qs}`, {
      headers: authHeaders(locale, guestId),
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : data?.data ?? [];
  } catch {
    return [];
  }
}

export async function addAddress(
  payload: {
    contact_person_name: string;
    contact_person_number: string;
    address_type: string;
    address: string;
    road?: string;
    house?: string;
    floor?: string;
    latitude?: string;
    longitude?: string;
  },
  locale: Locale = "ar",
  guestId?: string,
): Promise<{ ok: boolean; data: Record<string, unknown> }> {
  const res = await fetch(`${API_BASE_URL}/api/v1/customer/address/add`, {
    method: "POST",
    headers: authHeaders(locale, guestId),
    body: JSON.stringify(payload),
  });
  const data = (await res.json().catch(() => ({}))) as Record<string, unknown>;
  return { ok: res.ok, data };
}

export async function updateAddress(
  id: number,
  payload: {
    contact_person_name: string;
    contact_person_number: string;
    address_type: string;
    address: string;
    road?: string;
    house?: string;
    floor?: string;
  },
  locale: Locale = "ar",
): Promise<{ ok: boolean; data: Record<string, unknown> }> {
  const res = await fetch(`${API_BASE_URL}/api/v1/customer/address/update/${id}`, {
    method: "PUT",
    headers: authHeaders(locale),
    body: JSON.stringify(payload),
  });
  const data = (await res.json().catch(() => ({}))) as Record<string, unknown>;
  return { ok: res.ok, data };
}

export async function deleteAddress(
  id: number,
  locale: Locale = "ar",
): Promise<{ ok: boolean; data: Record<string, unknown> }> {
  const res = await fetch(`${API_BASE_URL}/api/v1/customer/address/delete?address_id=${id}`, {
    method: "DELETE",
    headers: authHeaders(locale),
  });
  const data = (await res.json().catch(() => ({}))) as Record<string, unknown>;
  return { ok: res.ok, data };
}

export async function fetchOrders(locale: Locale = "ar"): Promise<CustomerOrder[]> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/customer/order/list`, {
      headers: authHeaders(locale),
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : data?.data ?? [];
  } catch {
    return [];
  }
}

export async function fetchOrderDetails(orderId: number, locale: Locale = "ar"): Promise<OrderDetailItem[]> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/customer/order/details`, {
      method: "POST",
      headers: authHeaders(locale),
      body: JSON.stringify({ order_id: orderId }),
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : data?.data ?? [];
  } catch {
    return [];
  }
}

export type TrackedOrderDetailItem = {
  id: number;
  product_id: number;
  quantity: number;
  price: number;
  discount_on_product?: number;
  tax_amount?: number;
  variant?: string | null;
  product_details?:
    | string
    | {
        name?: string;
        image?: string[] | string;
        image_fullpath?: string[];
      };
};

export type TrackedOrder = {
  id: number;
  order_amount: number;
  delivery_charge?: number;
  coupon_code?: string | null;
  coupon_discount_amount?: number;
  extra_discount?: number;
  total_tax_amount?: number;
  order_status: string;
  payment_status: string;
  payment_method: string;
  delivery_date?: string | null;
  order_note?: string | null;
  created_at: string;
  updated_at?: string;
  details?: TrackedOrderDetailItem[];
  delivery_address?: CustomerAddress | null;
  delivery_man?: {
    f_name?: string;
    l_name?: string;
    phone?: string;
  } | null;
  offline_payment?: {
    status?: number;
    receipt_image_full_url?: string | null;
  } | null;
  offline_payment_information?: {
    payment_name?: string;
    payment_note?: string;
    method_fields?: Array<Record<string, string>>;
    method_information?: Array<Record<string, string>>;
  } | null;
};

export async function trackOrderById(
  orderId: number | string,
  locale: Locale = "ar",
): Promise<TrackedOrder | null> {
  try {
    const guestId =
      typeof window !== "undefined" ? localStorage.getItem("bh_guest_id") || undefined : undefined;
    const res = await fetch(`${API_BASE_URL}/api/v1/customer/order/track`, {
      method: "POST",
      headers: authHeaders(locale, guestId),
      body: JSON.stringify({ order_id: orderId }),
    });
    if (!res.ok) return null;
    return (await res.json()) as TrackedOrder;
  } catch {
    return null;
  }
}

export async function cancelOrder(
  orderId: number,
  locale: Locale = "ar",
): Promise<{ ok: boolean; data: Record<string, unknown> }> {
  const res = await fetch(`${API_BASE_URL}/api/v1/customer/order/cancel`, {
    method: "PUT",
    headers: authHeaders(locale),
    body: JSON.stringify({ order_id: orderId }),
  });
  const data = (await res.json().catch(() => ({}))) as Record<string, unknown>;
  return { ok: res.ok, data };
}

export async function updateCustomerProfile(
  payload: {
    f_name: string;
    l_name: string;
    email: string;
    phone: string;
    password?: string;
  },
  locale: Locale = "ar",
): Promise<{ ok: boolean; data: Record<string, unknown> }> {
  const res = await fetch(`${API_BASE_URL}/api/v1/customer/update-profile`, {
    method: "PUT",
    headers: authHeaders(locale),
    body: JSON.stringify(payload),
  });
  const data = (await res.json().catch(() => ({}))) as Record<string, unknown>;
  return { ok: res.ok, data };
}

export type MapPlaceSuggestion = {
  placeId: string;
  label: string;
};

export async function searchMapPlaces(
  searchText: string,
  locale: Locale = "ar",
): Promise<MapPlaceSuggestion[]> {
  if (!searchText.trim()) return [];
  try {
    const res = await fetch(
      `${API_BASE_URL}/api/v1/mapapi/place-api-autocomplete?search_text=${encodeURIComponent(searchText.trim())}`,
      {
        headers: { Accept: "application/json", "X-localization": locale },
      },
    );
    if (!res.ok) return [];
    const data = (await res.json()) as {
      suggestions?: Array<{
        placePrediction?: {
          placeId?: string;
          text?: { text?: string };
          structuredFormat?: { mainText?: { text?: string }; secondaryText?: { text?: string } };
        };
      }>;
    };
    return (data.suggestions ?? [])
      .map((s) => {
        const pred = s.placePrediction;
        if (!pred?.placeId) return null;
        const label =
          pred.text?.text ||
          [pred.structuredFormat?.mainText?.text, pred.structuredFormat?.secondaryText?.text]
            .filter(Boolean)
            .join(", ");
        return label ? { placeId: pred.placeId, label } : null;
      })
      .filter((item): item is MapPlaceSuggestion => Boolean(item));
  } catch {
    return [];
  }
}

export async function getMapPlaceDetails(
  placeId: string,
  locale: Locale = "ar",
): Promise<{ latitude: number; longitude: number; address?: string } | null> {
  if (!placeId) return null;
  try {
    const res = await fetch(
      `${API_BASE_URL}/api/v1/mapapi/place-api-details?placeid=${encodeURIComponent(placeId)}`,
      {
        headers: { Accept: "application/json", "X-localization": locale },
      },
    );
    if (!res.ok) return null;
    const data = (await res.json()) as {
      location?: { latitude?: number; longitude?: number };
      formattedAddress?: string;
      displayName?: { text?: string };
    };
    const lat = data.location?.latitude;
    const lng = data.location?.longitude;
    if (typeof lat !== "number" || typeof lng !== "number") return null;
    return {
      latitude: lat,
      longitude: lng,
      address: data.formattedAddress || data.displayName?.text,
    };
  } catch {
    return null;
  }
}

export async function reverseGeocodeLocation(
  lat: number,
  lng: number,
  locale: Locale = "ar",
): Promise<string | null> {
  try {
    const res = await fetch(
      `${API_BASE_URL}/api/v1/mapapi/geocode-api?lat=${lat}&lng=${lng}`,
      {
        headers: { Accept: "application/json", "X-localization": locale },
      },
    );
    if (!res.ok) return null;
    const data = (await res.json()) as {
      results?: Array<{ formatted_address?: string }>;
    };
    return data.results?.[0]?.formatted_address ?? null;
  } catch {
    return null;
  }
}

export async function fetchNotifications(locale: Locale = "ar"): Promise<NotificationItem[]> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/notifications`, {
      headers: authHeaders(locale),
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : data?.data ?? [];
  } catch {
    return [];
  }
}
