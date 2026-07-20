from __future__ import annotations

from typing import Any


class LiquidityAnalyzer:
    """Simple liquidity metric using the reciprocal of spread."""

    def analyze(self, payload: dict[str, Any]) -> float:
        spread = payload.get("spread", 0.0)
        return 0.0 if spread == 0 else 1.0 / spread
