import type { JsonRecord, Pagination } from "@/lib/types";

export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

async function readJson(response: Response): Promise<JsonRecord> {
  const text = await response.text();
  if (!text) return {};

  try {
    return JSON.parse(text) as JsonRecord;
  } catch {
    throw new ApiError("The server returned an invalid response.", response.status);
  }
}

async function csrfToken(): Promise<string> {
  const response = await fetch("/backend/auth/csrf", {
    credentials: "include",
    headers: { Accept: "application/json" },
  });
  const payload = await readJson(response);
  if (!response.ok || typeof payload.token !== "string") {
    throw new ApiError("Unable to establish a secure session.", response.status);
  }
  return payload.token;
}

export async function api<T = JsonRecord>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const method = (options.method ?? "GET").toUpperCase();
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  if (!["GET", "HEAD", "OPTIONS"].includes(method)) {
    headers.set("X-CSRF-TOKEN", await csrfToken());
    if (options.body && !(options.body instanceof FormData)) {
      headers.set("Content-Type", "application/json");
    }
  }

  const response = await fetch(`/backend/${path.replace(/^\//, "")}`, {
    ...options,
    method,
    headers,
    credentials: "include",
    cache: "no-store",
  });
  const payload = await readJson(response);

  if (!response.ok) {
    const errors = payload.errors as Record<string, string[]> | undefined;
    const firstError = errors ? Object.values(errors).flat()[0] : undefined;
    throw new ApiError(
      firstError ?? (payload.message as string | undefined) ?? "Request failed.",
      response.status,
      errors,
    );
  }

  return payload as T;
}

export function collectionFrom(payload: JsonRecord): {
  rows: JsonRecord[];
  pagination: Pagination;
} {
  const outer = payload.data ?? payload;

  if (Array.isArray(outer)) {
    return { rows: outer as JsonRecord[], pagination: {} };
  }

  if (outer && typeof outer === "object") {
    const record = outer as JsonRecord;
    if (Array.isArray(record.data)) {
      return { rows: record.data as JsonRecord[], pagination: record as Pagination };
    }
  }

  return { rows: [], pagination: {} };
}

export function valueAt(record: JsonRecord, path: string): unknown {
  return path.split(".").reduce<unknown>((value, key) => {
    if (!value || typeof value !== "object") return undefined;
    return (value as JsonRecord)[key];
  }, record);
}
