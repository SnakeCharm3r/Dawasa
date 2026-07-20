from __future__ import annotations

from typing import Any


class VolatilityAnalyzer:
    """Simple volatility metric based on spread variation."""

    def analyze(self, payload: dict[str, Any]) -> float:
        spread = payload.get("spread", 0.0)
        return abs(spread)
