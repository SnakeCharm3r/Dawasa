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
 * Classic DOM ladder: two columns (asks top-right, bids bottom-left) with the
 * spread/mid in the middle. Whales are outlined. Prices flash when they tick.
 */
export default function Ladder({ bids, asks, whaleSize, digits = 2, prevPrices }) {
  const renderCell = (lvl, side) => {
    const size = lvl.size || 0;
    const isWhale = size >= whaleSize;
    const prev = prevPrices ? prevPrices[lvl.quote_id] : undefined;
    let flash = "";
    if (prev != null && lvl.price != null) {
      if (lvl.price > prev) flash = "flash-up";
      else if (lvl.price < prev) flash = "flash-down";
    }
    return (
      <div key={`${side}-${lvl.quote_id}`} className={`cell ${side} ${isWhale ? "whale" : ""} ${flash}`}>
        <span className="px">{fmtPrice(lvl.price, digits)}</span>
        <span className="sz">{fmtSize(size)}</span>
      </div>
    );
  };

  if (asks.length === 0 && bids.length === 0) {
    return <div className="empty">Waiting for depth data…</div>;
  }

  const bestBid = bids.length ? bids[0].price : null;
  const bestAsk = asks.length ? asks[0].price : null;
  const mid = bestBid != null && bestAsk != null ? (bestBid + bestAsk) / 2 : bestBid || bestAsk;

  return (
    <div className="ladder">
      <div className="col asks">
        {asks.map((l) => renderCell(l, "ask"))}
      </div>
      <div className="col bids">
        <div className="mid">
          {mid != null ? fmtPrice(mid, digits) : "-"}
          {bestBid != null && bestAsk != null ? (
            <div style={{ fontSize: 11, fontWeight: 400, opacity: 0.8 }}>
              spread {fmtPrice(bestAsk - bestBid, digits)}
            </div>
          ) : null}
        </div>
        {bids.map((l) => renderCell(l, "bid"))}
      </div>
    </div>
  );
}
