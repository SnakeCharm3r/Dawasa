from __future__ import annotations

from collections import defaultdict
from datetime import datetime, timezone
from typing import Any, Iterable

from .models import NormalizedDeal, NormalizedTrade


ENTRY_NAMES = {0: "in", 1: "out", 2: "inout", 3: "out_by"}


def _dict(deal: Any) -> dict[str, Any]:
    if hasattr(deal, "_asdict"):
        return dict(deal._asdict())
    return dict(deal)


def _iso_time(raw: dict[str, Any]) -> str:
    milliseconds = raw.get("time_msc")
    seconds = float(milliseconds) / 1000 if milliseconds else float(raw["time"])
    return datetime.fromtimestamp(seconds, tz=timezone.utc).isoformat()


def normalize_deals(raw_deals: Iterable[Any], constants: dict[str, int]) -> list[NormalizedDeal]:
    entry_names = {
        constants.get("DEAL_ENTRY_IN", 0): "in",
        constants.get("DEAL_ENTRY_OUT", 1): "out",
        constants.get("DEAL_ENTRY_INOUT", 2): "inout",
        constants.get("DEAL_ENTRY_OUT_BY", 3): "out_by",
    }
    buy = constants.get("DEAL_TYPE_BUY", 0)
    sell = constants.get("DEAL_TYPE_SELL", 1)
    by_ticket: dict[str, NormalizedDeal] = {}
    for value in raw_deals:
        raw = _dict(value)
        deal_type = int(raw.get("type", -1))
        if deal_type not in {buy, sell}:
            continue
        entry_type = entry_names.get(int(raw.get("entry", -1)))
        position_id = str(raw.get("position_id") or "")
        ticket = str(raw.get("ticket") or "")
        if not entry_type or not position_id or not ticket:
            continue
        side = "buy" if deal_type == buy else "sell"
        by_ticket[ticket] = NormalizedDeal(
            broker_deal_id=ticket,
            broker_order_id=str(raw.get("order")) if raw.get("order") else None,
            broker_position_id=position_id,
            entry_type=entry_type,
            deal_type=side,
            symbol=str(raw.get("symbol") or ""),
            side=side,
            deal_time=_iso_time(raw),
            price=float(raw.get("price") or 0),
            volume=float(raw.get("volume") or 0),
            profit=float(raw.get("profit") or 0),
            commission=float(raw.get("commission") or 0),
            swap=float(raw.get("swap") or 0),
            fee=float(raw.get("fee") or 0),
            magic_number=int(raw["magic"]) if raw.get("magic") is not None else None,
            comment=str(raw.get("comment")) if raw.get("comment") else None,
            raw_metadata={
                key: raw.get(key)
                for key in ("reason", "external_id", "sl", "tp")
                if key in raw
            },
        )
    return sorted(by_ticket.values(), key=lambda deal: (deal.deal_time, int(deal.broker_deal_id)))


def _weighted_price(deals: list[NormalizedDeal]) -> float:
    volume = sum(deal.volume for deal in deals)
    if volume <= 0:
        return 0
    return sum(deal.price * deal.volume for deal in deals) / volume


def _last_nonzero(deals: list[NormalizedDeal], key: str) -> float | None:
    for deal in reversed(deals):
        value = deal.raw_metadata.get(key)
        if value not in (None, 0, 0.0, ""):
            return float(value)
    return None


def reconstruct_closed_positions(deals: Iterable[NormalizedDeal]) -> list[NormalizedTrade]:
    grouped: dict[str, list[NormalizedDeal]] = defaultdict(list)
    for deal in deals:
        grouped[deal.broker_position_id].append(deal)

    trades: list[NormalizedTrade] = []
    for position_id, position_deals in grouped.items():
        ordered = sorted(position_deals, key=lambda deal: (deal.deal_time, int(deal.broker_deal_id)))
        entries = [deal for deal in ordered if deal.entry_type == "in"]
        exits = [deal for deal in ordered if deal.entry_type in {"out", "out_by", "inout"}]
        if not entries or not exits:
            continue
        entry_volume = sum(deal.volume for deal in entries)
        exit_volume = sum(deal.volume for deal in exits)
        if entry_volume <= 0 or exit_volume + 1e-8 < entry_volume:
            continue
        side = entries[0].side
        if side not in {"buy", "sell"}:
            continue
        commission = sum(deal.commission for deal in ordered)
        swap = sum(deal.swap for deal in ordered)
        fee = sum(deal.fee for deal in ordered)
        gross_profit = sum(deal.profit for deal in ordered)
        trades.append(
            NormalizedTrade(
                broker_position_id=position_id,
                broker_order_id=entries[0].broker_order_id,
                broker_deal_id=exits[-1].broker_deal_id,
                broker_deal_ids=[deal.broker_deal_id for deal in ordered],
                symbol=entries[0].symbol,
                side=side,
                volume=min(entry_volume, exit_volume),
                entry_price=_weighted_price(entries),
                exit_price=_weighted_price(exits),
                open_time=entries[0].deal_time,
                close_time=exits[-1].deal_time,
                stop_loss=_last_nonzero(ordered, "sl"),
                take_profit=_last_nonzero(ordered, "tp"),
                commission=commission,
                swap=swap,
                fee=fee,
                gross_profit=gross_profit,
                net_profit=gross_profit + commission + swap + fee,
                magic_number=entries[0].magic_number,
                comment=exits[-1].comment or entries[0].comment,
                raw_metadata={"entry_deal_count": len(entries), "exit_deal_count": len(exits)},
            )
        )
    return sorted(trades, key=lambda trade: (trade.close_time, trade.broker_position_id))
