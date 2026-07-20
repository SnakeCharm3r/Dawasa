from __future__ import annotations

from typing import Any


class SpreadAnalyzer:
    """Reusable spread analysis helper."""

    def analyze(self, payload: dict[str, Any]) -> float:
        bid = payload.get("bid", 0.0)
        ask = payload.get("ask", 0.0)
        return ask - bid
