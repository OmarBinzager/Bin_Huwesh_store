"use client";

import { createContext, useContext, type ReactNode } from "react";

type BrandConfig = {
  logoUrl: string | null;
};

const BrandConfigContext = createContext<BrandConfig>({ logoUrl: null });

export function BrandConfigProvider({
  logoUrl,
  children,
}: {
  logoUrl: string | null;
  children: ReactNode;
}) {
  return (
    <BrandConfigContext.Provider value={{ logoUrl }}>
      {children}
    </BrandConfigContext.Provider>
  );
}

export function useBrandConfig(): BrandConfig {
  return useContext(BrandConfigContext);
}
