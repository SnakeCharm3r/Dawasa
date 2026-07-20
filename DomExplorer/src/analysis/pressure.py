from __future__ import annotations

from typing import Any


class PressureAnalyzer:
    """Pressure indicator based on the relative bid/ask imbalance."""

    def analyze(self, payload: dict[str, Any]) -> float:
        bid = payload.get("bid", 0.0)
        ask = payload.get("ask", 0.0)
        return (ask - bid) / max(bid, ask, 1.0)
