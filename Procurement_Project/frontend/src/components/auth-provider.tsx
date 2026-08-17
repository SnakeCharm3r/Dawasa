"use client";

import { api, ApiError } from "@/lib/api";
import type { AuthUser } from "@/lib/types";
import { usePathname, useRouter } from "next/navigation";
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";

type AuthContextValue = {
  user: AuthUser | null;
  loading: boolean;
  login: (email: string, password: string, remember: boolean) => Promise<void>;
  demoLogin: (account: string) => Promise<void>;
  logout: () => Promise<void>;
  hasRole: (...roles: string[]) => boolean;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();
  const pathname = usePathname();

  const refresh = useCallback(async () => {
    try {
      const response = await api<{ data: AuthUser }>("auth/me");
      setUser(response.data);
    } catch (error) {
      if (!(error instanceof ApiError) || error.status !== 401) {
        console.error(error);
      }
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  useEffect(() => {
    if (loading || !user) return;
    if (["/login", "/supplier-login"].includes(pathname)) {
      router.replace(user.roles.includes("supplier") ? "/supplier-dashboard" : "/dashboard");
    }
  }, [loading, pathname, router, user]);

  const login = useCallback(async (email: string, password: string, remember: boolean) => {
    const response = await api<{ data: AuthUser }>("auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password, remember }),
    });
    setUser(response.data);
    router.replace(response.data.roles.includes("supplier") ? "/supplier-dashboard" : "/dashboard");
    router.refresh();
  }, [router]);

  const demoLogin = useCallback(async (account: string) => {
    const response = await api<{ data: AuthUser }>("auth/demo-login", {
      method: "POST",
      body: JSON.stringify({ account }),
    });
    setUser(response.data);
    router.replace("/dashboard");
    router.refresh();
  }, [router]);

  const logout = useCallback(async () => {
    await api("auth/logout", { method: "POST" });
    setUser(null);
    router.replace("/login");
  }, [router]);

  const value = useMemo<AuthContextValue>(() => ({
    user,
    loading,
    login,
    demoLogin,
    logout,
    hasRole: (...roles: string[]) => Boolean(user?.roles.includes("ceo") || user?.roles.some((role) => roles.includes(role))),
    refresh,
  }), [demoLogin, loading, login, logout, refresh, user]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used inside AuthProvider");
  return context;
}
