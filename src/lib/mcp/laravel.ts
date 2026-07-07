import type { ToolContext } from "@lovable.dev/mcp-js";

/**
 * Shared helper for MCP tools that call the Laravel API.
 *
 * Auth model (see backend-reference/docs/MCP_TOOL_AUTHORIZATION.md):
 * the caller's bearer token is forwarded to Laravel so the same Policy +
 * FormRequest::withValidator() layer runs identically. Until OAuth is wired
 * into defineMcp, we fall back to a server-issued LARAVEL_API_TOKEN so the
 * tools can be exercised end-to-end in staging. Do NOT ship the fallback to
 * a multi-tenant production deployment.
 */
export type LaravelResult<T = unknown> =
  | { ok: true; status: number; data: T }
  | { ok: false; status: number; error: string; details?: unknown };

function baseUrl(): string {
  const url = process.env.LARAVEL_API_URL;
  if (!url) throw new Error("LARAVEL_API_URL is not configured on the server.");
  return url.replace(/\/+$/, "");
}

function bearer(ctx?: ToolContext): string | null {
  const fromCtx = ctx && typeof ctx.getToken === "function" ? ctx.getToken() : null;
  return fromCtx ?? process.env.LARAVEL_API_TOKEN ?? null;
}

export async function callLaravel<T = unknown>(
  method: "GET" | "POST" | "PATCH" | "DELETE",
  path: string,
  opts: { ctx?: ToolContext; query?: Record<string, string | number | undefined>; body?: unknown } = {},
): Promise<LaravelResult<T>> {
  const token = bearer(opts.ctx);
  if (!token) {
    return {
      ok: false,
      status: 401,
      error:
        "No bearer token available. Connect via OAuth or set LARAVEL_API_TOKEN for server-to-server use.",
    };
  }

  const qs = opts.query
    ? "?" +
      Object.entries(opts.query)
        .filter(([, v]) => v !== undefined && v !== "")
        .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`)
        .join("&")
    : "";
  const url = `${baseUrl()}/api/v1${path.startsWith("/") ? path : `/${path}`}${qs}`;

  const headers: Record<string, string> = {
    Accept: "application/json",
    Authorization: `Bearer ${token}`,
  };
  if (opts.body !== undefined) headers["Content-Type"] = "application/json";

  let res: Response;
  try {
    res = await fetch(url, {
      method,
      headers,
      body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
    });
  } catch (e) {
    return { ok: false, status: 0, error: `Network error calling Laravel: ${(e as Error).message}` };
  }

  const text = await res.text();
  let parsed: unknown = text;
  if (text) {
    try {
      parsed = JSON.parse(text);
    } catch {
      /* keep raw text */
    }
  }

  if (!res.ok) {
    let message = res.statusText || `HTTP ${res.status}`;
    if (parsed && typeof parsed === "object" && "message" in parsed) {
      message = String((parsed as { message: unknown }).message);
    }
    return { ok: false, status: res.status, error: message, details: parsed };
  }

  return { ok: true, status: res.status, data: parsed as T };
}

export function asToolResult(result: LaravelResult): {
  content: { type: "text"; text: string }[];
  structuredContent?: Record<string, unknown>;
  isError?: boolean;
} {
  if (!result.ok) {
    return {
      content: [{ type: "text", text: `Laravel API error (${result.status}): ${result.error}` }],
      isError: true,
    };
  }
  const payload = result.data as Record<string, unknown>;
  return {
    content: [{ type: "text", text: JSON.stringify(payload, null, 2) }],
    structuredContent: typeof payload === "object" && payload !== null ? payload : { value: payload },
  };
}
