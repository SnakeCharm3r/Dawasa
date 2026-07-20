from __future__ import annotations

from typing import Any


class ImbalanceAnalyzer:
    """Simple imbalance metric using the ratio of bid and ask."""

    def analyze(self, payload: dict[str, Any]) -> float:
        bid = payload.get("bid", 0.0)
        ask = payload.get("ask", 0.0)
        return (bid - ask) / max(bid, ask, 1.0)
