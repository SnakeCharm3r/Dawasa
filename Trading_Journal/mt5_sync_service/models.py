from __future__ import annotations

from dataclasses import asdict, dataclass, field
from typing import Any


@dataclass(frozen=True)
class NormalizedDeal:
    broker_deal_id: str
    broker_order_id: str | None
    broker_position_id: str
    entry_type: str
    deal_type: str
    symbol: str
    side: str | None
    deal_time: str
    price: float
    volume: float
    profit: float = 0
    commission: float = 0
    swap: float = 0
    fee: float = 0
    magic_number: int | None = None
    comment: str | None = None
    raw_metadata: dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)


@dataclass(frozen=True)
class NormalizedTrade:
    broker_position_id: str
    broker_order_id: str | None
    broker_deal_id: str | None
    broker_deal_ids: list[str]
    symbol: str
    side: str
    volume: float
    entry_price: float
    exit_price: float
    open_time: str
    close_time: str
    stop_loss: float | None
    take_profit: float | None
    commission: float
    swap: float
    fee: float
    gross_profit: float
    net_profit: float
    magic_number: int | None
    comment: str | None
    raw_metadata: dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)
