import React from "react";

function fmtPrice(v, digits) {
  if (v == null) return "-";
  return Number(v).toFixed(digits);
}

function fmtSize(v) {
  if (v == null) return "-";
  if (v >= 1_000_000) return (v / 1_000_000).toFixed(2) + "M";
  if (v >= 1_000) return (v / 1_000).toFixed(1) + "k";
  return Number(v).toFixed(0);
}

/**
 * Order-book heatmap. Renders asks (top, red) and bids (bottom, green) as a
 * ladder where each row's bar width is proportional to size. Large resting
 * orders ("whales") are outlined and printed on the map.
 *
 * `prevPrices` lets us flash a row when its price ticks up/down.
 */
export default function Heatmap({ bids, asks, whaleSize, digits = 2, prevPrices }) {
  const all = [...asks, ...bids];
  const maxSize = all.reduce((m, l) => Math.max(m, l.size || 0), 0) || 1;

  const renderRow = (lvl, side) => {
    const size = lvl.size || 0;
    const pct = Math.max(4, Math.round((size / maxSize) * 100));
    const isWhale = size >= whaleSize;
    const prev = prevPrices ? prevPrices[lvl.quote_id] : undefined;
    let flash = "";
    if (prev != null && lvl.price != null) {
      if (lvl.price > prev) flash = "flash-up";
      else if (lvl.price < prev) flash = "flash-down";
    }
    return (
      <div key={`${side}-${lvl.quote_id}`} className={`ladder-row ${side} ${isWhale ? "whale" : ""}`}>
        <div className="price">{fmtPrice(lvl.price, digits)}</div>
        <div className="ladder-bar" style={{ width: `${pct}%` }}>
          {isWhale ? <span className="whale-tag">WHALE {fmtSize(size)}</span> : null}
        </div>
        <div className="size">{fmtSize(size)}</div>
      </div>
    );
  };

  return (
    <div className="heatmap">
      {asks.length === 0 && bids.length === 0 ? (
        <div className="empty">Waiting for depth data…</div>
      ) : (
        <>
          {asks.map((l) => renderRow(l, "ask"))}
          <div className="spread-row">ORDER BOOK</div>
          {bids.map((l) => renderRow(l, "bid"))}
        </>
      )}
    </div>
  );
}
