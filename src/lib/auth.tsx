import { useSyncExternalStore } from "react";
import type { RoleCode, User } from "./api/types";
import { login, session } from "./api/client";

type Listener = () => void;
const listeners = new Set<Listener>();
const notify = () => listeners.forEach((listener) => listener());
const subscribe = (listener: Listener) => { listeners.add(listener); return () => listeners.delete(listener); };
const readStoredUser = (): User | null => {
  if (typeof window === "undefined") return null;
  const raw = window.localStorage.getItem("lifters-touch-user");
  try { return raw ? (JSON.parse(raw) as User) : null; } catch { return null; }
};
let snapshot: User | null | undefined;
const getSnapshot = () => { if (snapshot === undefined) snapshot = readStoredUser(); return snapshot; };

export const auth = {
  getUser: () => readStoredUser(),
  isAuthenticated: () => Boolean(session.getToken() && session.getUser()),
  hasRole: (role: RoleCode) => Boolean(session.getUser()?.roles.includes(role)),
  hasAnyRole: (roles: RoleCode[]) => Boolean(session.getUser()?.roles.some((role) => roles.includes(role))),
  login: async (email: string, password: string) => { const result = await login(email, password); session.set(result); snapshot = result.user; notify(); },
  logout: () => { session.clear(); snapshot = null; notify(); },
};

export function useAuth() {
  const user = useSyncExternalStore(subscribe, getSnapshot, () => null);
  return { user, isAuthenticated: Boolean(user && session.getToken()), login: auth.login, logout: auth.logout, hasRole: auth.hasRole, hasAnyRole: auth.hasAnyRole };
}