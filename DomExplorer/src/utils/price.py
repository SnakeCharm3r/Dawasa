from __future__ import annotations

from typing import Any, Dict, Optional


class PriceNormalizer:
    """Centralizes cTrader integer-price normalization.

    cTrader delivers prices as integers scaled by ``10 ** digits`` (per symbol).
    This helper converts them to real-world floats so that every consumer -
    the console monitor, the analysis persistence, and the web API - agrees
    on the same normalized values.
    """

    def __init__(self, digits_by_symbol: Optional[Dict[str, int]] = None, default_digits: int = 0) -> None:
        self._digits_by_symbol: Dict[str, int] = {str(k).upper(): int(v) for k, v in (digits_by_symbol or {}).items()}
        self._default_digits = int(default_digits)

    def digits_for(self, symbol: str) -> int:
        if symbol is None:
            return self._default_digits
        return self._digits_by_symbol.get(str(symbol).upper(), self._default_digits)

    def set_digits(self, symbol: str, digits: int) -> None:
        if symbol is None:
            return
        self._digits_by_symbol[str(symbol).upper()] = int(digits)

    def normalize(self, raw_price: Any, symbol: str) -> Optional[float]:
        if raw_price is None:
            return None
        try:
            raw = float(raw_price)
        except (TypeError, ValueError):
            return None

        digits = self.digits_for(symbol)
        if digits <= 0:
            return raw
        return raw / (10 ** digits)


def build_normalizer_from_config(config: Any, focus_symbol: str) -> PriceNormalizer:
    """Build a normalizer from the application config.

    Honors ``FOCUS_SYMBOL_DIGITS`` plus a ``SYMBOL_DIGITS`` mapping encoded as
    ``SYM1:digits,SYM2:digits`` (e.g. ``XAUUSD:2,GBPUSD:5,GBPCHF:5``).
    """
    digits_by_symbol: Dict[str, int] = {}

    focus_digits = getattr(config, "FOCUS_SYMBOL_DIGITS", None)
    if focus_digits is not None:
        try:
            digits_by_symbol[str(focus_symbol).upper()] = int(focus_digits)
        except (TypeError, ValueError):
            pass

    mapping = getattr(config, "SYMBOL_DIGITS", None)
    if mapping:
        for pair in str(mapping).split(","):
            pair = pair.strip()
            if not pair or ":" not in pair:
                continue
            name, _, value = pair.partition(":")
            try:
                digits_by_symbol[name.strip().upper()] = int(value.strip())
            except (TypeError, ValueError):
                continue

    return PriceNormalizer(digits_by_symbol)
