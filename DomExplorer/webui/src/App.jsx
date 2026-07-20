import React, { useEffect, useMemo, useRef, useState } from "react";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip,
} from "chart.js";
import { Line } from "react-chartjs-2";
import { api } from "./api";
import Heatmap from "./Heatmap";
import Ladder from "./Ladder";

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip);

const METRIC_CARDS = [
  { key: "spread", label: "Spread", fmt: (v) => (v == null ? "-" : v.toFixed(2)) },
  { key: "imbalance", label: "Imbalance", fmt: (v) => (v == null ? "-" : v.toFixed(3)) },
  { key: "pressure", label: "Pressure", fmt: (v) => (v == null ? "-" : v.toFixed(3)) },
  { key: "liquidity", label: "Liquidity", fmt: (v) => (v == null ? "-" : v.toFixed(4)) },
  { key: "dom_bid_volume", label: "Bid Vol", fmt: (v) => (v == null ? "-" : Math.round(v).toLocaleString()) },
  { key: "dom_ask_volume", label: "Ask Vol", fmt: (v) => (v == null ? "-" : Math.round(v).toLocaleString()) },
];

const WHALE_SIZE = 500;

// Symbols that trade 24/7 and are never subject to the weekend-close signal.
const ALWAYS_OPEN = ["BTCUSD"];

const VIEWS = ["Heatmap", "Ladder", "Analytics"];

function Sparkline({ data, color }) {
  const chartData = {
    labels: data.map((_, i) => i),
    datasets: [
      {
        data: data.map((d) => d.value),
        borderColor: color,
        backgroundColor: color + "22",
        borderWidth: 1.5,
        fill: true,
        pointRadius: 0,
        tension: 0.3,
      },
    ],
  };
  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { tooltip: { enabled: false }, legend: { display: false } },
    scales: { x: { display: false }, y: { display: false } },
    animation: false,
  };
  return (
    <div style={{ height: 60 }}>
      <Line data={chartData} options={options} />
    </div>
  );
}

export default function App() {
  const [symbols, setSymbols] = useState([]);
  const [symbol, setSymbol] = useState("XAUUSD");
  const [latest, setLatest] = useState({});
  const [series, setSeries] = useState({});
  const [depth, setDepth] = useState({ bids: [], asks: [] });
  const [logs, setLogs] = useState([]);
  const [live, setLive] = useState(false);
  const [digits, setDigits] = useState(2);
  const [market, setMarket] = useState(null);
  const [view, setView] = useState("Heatmap");
  const [query, setQuery] = useState("");

  const prevPrices = useRef({});
  const symbolRef = useRef(symbol);
  symbolRef.current = symbol;

  useEffect(() => {
    api
      .symbols()
      .then((rows) => {
        setSymbols(rows);
        if (rows.length && !rows.some((r) => r.symbol_name === symbolRef.current)) {
          setSymbol(rows[0].symbol_name);
        }
      })
      .catch(() => {});
  }, []);

  useEffect(() => {
    let active = true;
    const tick = async () => {
      try {
        const [lat, dep, lg] = await Promise.all([
          api.latest(symbol),
          api.depth(symbol),
          api.logs(60),
        ]);
        if (!active) return;
        setLatest(lat.metrics || {});
        setLogs(lg);
        setLive(true);
        const next = { bids: dep.bids || [], asks: dep.asks || [] };
        const pp = {};
        [...next.bids, ...next.asks].forEach((l) => (pp[l.quote_id] = l.price));
        prevPrices.current = pp;
        setDepth(next);
      } catch (e) {
        if (active) setLive(false);
      }
    };
    tick();
    const id = setInterval(tick, 1500);
    return () => {
      active = false;
      clearInterval(id);
    };
  }, [symbol]);

  useEffect(() => {
    let active = true;
    const metrics = ["spread", "imbalance", "dom_bid_volume", "dom_ask_volume"];
    const load = () =>
      Promise.all(metrics.map((m) => api.metricSeries(symbol, m, 120).catch(() => ({ series: [] }))))
        .then((res) => {
          if (!active) return;
          const out = {};
          metrics.forEach((m, i) => (out[m] = res[i].series || []));
          setSeries(out);
        })
        .catch(() => {});
    load();
    const id = setInterval(load, 4000);
    return () => {
      active = false;
      clearInterval(id);
    };
  }, [symbol]);

  useEffect(() => {
    let active = true;
    const load = () =>
      api
        .marketStatus()
        .then((s) => active && setMarket(s))
        .catch(() => {});
    load();
    const id = setInterval(load, 60000);
    return () => {
      active = false;
      clearInterval(id);
    };
  }, []);

  const bestBid = depth.bids.length ? depth.bids[0].price : null;
  const bestAsk = depth.asks.length ? depth.asks[0].price : null;
  const isCrypto = ALWAYS_OPEN.includes(symbol);
  const marketClosed = market && market.closed;

  const filtered = useMemo(() => {
    const q = query.trim().toUpperCase();
    if (!q) return symbols;
    return symbols.filter((s) => s.symbol_name.toUpperCase().includes(q));
  }, [symbols, query]);

  return (
    <div className="app">
      <aside className="sidebar">
        <div className="brand">
          Dom<span>Explorer</span>
        </div>
        <div className="section-label">Instruments</div>
        <div className="symbol-search">
          <input
            type="text"
            className="symbol-search-input"
            placeholder="Search symbols…"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
        </div>
        <div className="symbol-list">
          {filtered.map((s) => (
            <div
              key={s.symbol_id}
              className={`symbol-item ${s.symbol_name === symbol ? "active" : ""}`}
              onClick={() => setSymbol(s.symbol_name)}
            >
              <span>{s.symbol_name}</span>
              <span className="tick">
                {ALWAYS_OPEN.includes(s.symbol_name) ? "24/7" : s.symbol_name === symbol ? "●" : ""}
              </span>
            </div>
          ))}
          {filtered.length === 0 && <div className="symbol-item">No matches</div>}
          {symbols.length === 0 && <div className="symbol-item">Loading…</div>}
        </div>
        <div className="footer">
          <span className={`status-dot ${live ? "live" : ""}`} />
          {live ? "Live" : "Offline"}
        </div>
      </aside>

      <main className="content">
        {marketClosed && !isCrypto && (
          <div className="market-banner">
            ⏸ Markets closed{market && market.is_weekend ? " (weekend)" : ""} — traditional
            instruments are not trading. Crypto (BTCUSD) remains live 24/7.
          </div>
        )}

        <div className="topbar">
          <div>
            <h1>
              {symbol} {isCrypto && <span className="crypto-tag">24/7</span>}
            </h1>
            <div className="sub">
              Depth-of-market analytics · best bid {bestBid != null ? bestBid.toFixed(digits) : "-"} / ask{" "}
              {bestAsk != null ? bestAsk.toFixed(digits) : "-"}
            </div>
          </div>
        </div>

        <div className="cards">
          {METRIC_CARDS.map((m) => {
            const v = latest[m.key];
            return (
              <div className="card" key={m.key}>
                <div className="label">{m.label}</div>
                <div className={`value ${v == null ? "" : v >= 0 ? "up" : "down"}`}>
                  {v == null ? "-" : m.fmt(v)}
                </div>
              </div>
            );
          })}
        </div>

        <div className="tabs">
          {VIEWS.map((v) => (
            <div
              key={v}
              className={`tab ${view === v ? "active" : ""}`}
              onClick={() => setView(v)}
            >
              {v}
            </div>
          ))}
        </div>

        <div className="panel">
          <h2>
            {view === "Heatmap" && (
              <>Order Book Heatmap <span className="hint">— whales outlined & printed on the map</span></>
            )}
            {view === "Ladder" && (
              <>DOM Ladder <span className="hint">— classic bid/ask columns, whales outlined</span></>
            )}
            {view === "Analytics" && (
              <>Live Metric Trends <span className="hint">— rolling series</span></>
            )}
          </h2>

          {view === "Heatmap" && (
            <Heatmap
              bids={depth.bids}
              asks={depth.asks}
              whaleSize={WHALE_SIZE}
              digits={digits}
              prevPrices={prevPrices.current}
            />
          )}

          {view === "Ladder" && (
            <Ladder
              bids={depth.bids}
              asks={depth.asks}
              whaleSize={WHALE_SIZE}
              digits={digits}
              prevPrices={prevPrices.current}
            />
          )}

          {view === "Analytics" && (
            <div style={{ display: "grid", gap: 12 }}>
              <div>
                <div className="label" style={{ color: "var(--filament-muted)", fontSize: 12 }}>
                  Bid / Ask Volume
                </div>
                <Sparkline data={series["dom_bid_volume"] || []} color="#16a34a" />
                <Sparkline data={series["dom_ask_volume"] || []} color="#dc2626" />
              </div>
              <div>
                <div className="label" style={{ color: "var(--filament-muted)", fontSize: 12 }}>
                  Spread
                </div>
                <Sparkline data={series["spread"] || []} color="#4f46e5" />
              </div>
              <div>
                <div className="label" style={{ color: "var(--filament-muted)", fontSize: 12 }}>
                  Imbalance
                </div>
                <Sparkline data={series["imbalance"] || []} color="#0ea5e9" />
              </div>
            </div>
          )}
        </div>

        <div className="panel" style={{ marginTop: 16 }}>
          <h2>System Log</h2>
          <div className="log-feed">
            {logs.map((l, i) => (
              <div key={i} className={`lvl-${l.level}`}>
                [{l.created_at}] {l.level}: {l.message}
              </div>
            ))}
            {logs.length === 0 && <div className="empty">No logs yet.</div>}
          </div>
        </div>
      </main>
    </div>
  );
}
