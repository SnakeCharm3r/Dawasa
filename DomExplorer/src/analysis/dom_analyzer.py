from __future__ import annotations

from typing import Any, Dict, Optional


class DomAnalyzer:
    """Analyses a depth-of-market snapshot for a single instrument."""

    def analyze(self, depth: Optional[Dict[str, Any]]) -> Dict[str, Any]:
        if not depth:
            return {"bid_volume": 0.0, "ask_volume": 0.0, "imbalance": 0.0, "spread": 0.0}

        bids = depth.get("bids", [])
        asks = depth.get("asks", [])

        bid_volume = sum(level.get("size", 0.0) for level in bids)
        ask_volume = sum(level.get("size", 0.0) for level in asks)

        total = bid_volume + ask_volume
        imbalance = (bid_volume - ask_volume) / total if total else 0.0

        best_bid = bids[0]["price"] if bids else None
        best_ask = asks[0]["price"] if asks else None
        spread = (best_ask - best_bid) if best_bid is not None and best_ask is not None else 0.0

        return {
            "bid_volume": bid_volume,
            "ask_volume": ask_volume,
            "imbalance": imbalance,
            "spread": spread,
        }
