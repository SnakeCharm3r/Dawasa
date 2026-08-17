"use client";

import { useAuth } from "@/components/auth-provider";
import { api, collectionFrom } from "@/lib/api";
import type { JsonRecord } from "@/lib/types";
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";

type Entity = { id: number; name: string; code: string };
type EntityScope = {
  entities: Entity[];
  selectedEntityId: string;
  setSelectedEntityId: (value: string) => void;
};

const EntityScopeContext = createContext<EntityScope | null>(null);

export function EntityScopeProvider({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const [entities, setEntities] = useState<Entity[]>([]);
  const [selectedEntityId, setSelectedEntityIdState] = useState("");
  const isCeo = user?.roles.includes("ceo") ?? false;

  useEffect(() => {
    if (!isCeo) { setEntities([]); setSelectedEntityIdState(""); return; }
    const saved = window.sessionStorage.getItem("ceo_business_entity_id") ?? "";
    setSelectedEntityIdState(saved);
    void api<JsonRecord>("admin/entities?is_active=1&per_page=100")
      .then((payload) => setEntities(collectionFrom(payload).rows.map((entity) => ({ id: Number(entity.id), name: String(entity.name), code: String(entity.code) }))));
  }, [isCeo]);

  const setSelectedEntityId = useCallback((value: string) => {
    setSelectedEntityIdState(value);
    window.sessionStorage.setItem("ceo_business_entity_id", value);
  }, []);

  const value = useMemo(() => ({ entities, selectedEntityId, setSelectedEntityId }), [entities, selectedEntityId, setSelectedEntityId]);
  return <EntityScopeContext.Provider value={value}>{children}</EntityScopeContext.Provider>;
}

export function useEntityScope() {
  const context = useContext(EntityScopeContext);
  if (!context) throw new Error("useEntityScope must be used inside EntityScopeProvider");
  return context;
}
