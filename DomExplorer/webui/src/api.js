const BASE = "/api";

async function getJson(url) {
  const res = await fetch(`${BASE}${url}`);
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(body.error || `HTTP ${res.status}`);
  }
  return res.json();
}

export const api = {
  health: () => getJson("/health"),
  symbols: () => getJson("/symbols"),
  latest: (symbol) => getJson(`/symbols/${symbol}/latest`),
  metricSeries: (symbol, metric, limit = 240) =>
    getJson(`/symbols/${symbol}/metrics/${metric}?limit=${limit}`),
  depth: (symbol) => getJson(`/symbols/${symbol}/depth`),
  logs: (limit = 100) => getJson(`/logs?limit=${limit}`),
  marketStatus: () => getJson("/market-status"),
};
