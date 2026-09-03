import { mockDashboard, mockLogin } from "./mock";
import type { DashboardData, LoginResponse, User } from "./types";

const TOKEN_KEY = "lifters-touch-token";
const USER_KEY = "lifters-touch-user";
const isMock = import.meta.env.VITE_API_MODE !== "live";

export const session = {
  getToken: () => (typeof window === "undefined" ? null : window.localStorage.getItem(TOKEN_KEY)),
  getUser: (): User | null => {
    if (typeof window === "undefined") return null;
    const raw = window.localStorage.getItem(USER_KEY);
    return raw ? (JSON.parse(raw) as User) : null;
  },
  set: ({ token, user }: LoginResponse) => {
    if (typeof window !== "undefined") {
      window.localStorage.setItem(TOKEN_KEY, token);
      window.localStorage.setItem(USER_KEY, JSON.stringify(user));
    }
  },
  clear: () => {
    if (typeof window !== "undefined") {
      window.localStorage.removeItem(TOKEN_KEY);
      window.localStorage.removeItem(USER_KEY);
    }
  },
};

export async function login(email: string, password: string) {
  if (isMock) return mockLogin(email, password);
  const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/v1/login`, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify({ email, password }) });
  const payload = await response.json();
  if (!response.ok) throw new Error(payload.message ?? "Unable to sign in.");
  return { token: payload.token, user: payload.data };
}

export async function dashboard(user: User): Promise<DashboardData> {
  if (isMock) return mockDashboard(user);
  throw new Error("Dashboard API wiring is ready for the live contract but no dashboard endpoint is configured yet.");
}