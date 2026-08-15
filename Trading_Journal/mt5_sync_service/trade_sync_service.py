from __future__ import annotations

import logging
import time
from datetime import datetime, timedelta, timezone
from typing import Any

from .account_service import AccountService
from .api_client import JournalApiClient
from .config import Config
from .logging_config import log_event
from .mt5_client import Mt5Client
from .trade_normalizer import normalize_deals, reconstruct_closed_positions


logger = logging.getLogger(__name__)


def incremental_range(
    account: dict[str, Any], now: datetime, initial_days: int, overlap_minutes: int
) -> tuple[datetime, datetime]:
    cursor = account.get("last_deal_time")
    if cursor:
        parsed = datetime.fromisoformat(str(cursor).replace("Z", "+00:00"))
        return parsed - timedelta(minutes=overlap_minutes), now
    configured_start = account.get("history_start_at")
    if configured_start:
        return datetime.fromisoformat(str(configured_start).replace("Z", "+00:00")), now
    return now - timedelta(days=initial_days), now


class TradeSyncService:
    def __init__(
        self,
        config: Config,
        mt5_client: Mt5Client,
        account_service: AccountService,
        api_client: JournalApiClient,
    ) -> None:
        self.config = config
        self.mt5_client = mt5_client
        self.account_service = account_service
        self.api_client = api_client

    def sync_once(self) -> dict[str, Any]:
        started = time.monotonic()
        registered = self.api_client.get_account()
        date_from, date_to = incremental_range(
            registered,
            datetime.now(timezone.utc),
            self.config.initial_history_days,
            self.config.overlap_minutes,
        )
        log_event(
            logger,
            logging.INFO,
            "mt5_history_requested",
            account_id=self.config.trading_account_id,
            date_from=date_from.isoformat(),
            date_to=date_to.isoformat(),
        )
        snapshot = self.account_service.verified_snapshot()
        raw_deals = self.mt5_client.history_deals(date_from, date_to)
        constants = self.mt5_client.constants()
        deals = normalize_deals(raw_deals, constants)

        # An incremental overlap can contain a closing deal without the older entry.
        # Pull complete history only for those affected position IDs.
        position_ids = {deal.broker_position_id for deal in deals}
        enriched = list(deals)
        for position_id in position_ids:
            position_deals = [deal for deal in deals if deal.broker_position_id == position_id]
            has_entry = any(deal.entry_type == "in" for deal in position_deals)
            has_exit = any(deal.entry_type in {"out", "out_by", "inout"} for deal in position_deals)
            if has_exit and not has_entry:
                complete = self.mt5_client.history_deals_for_position(int(position_id))
                enriched.extend(normalize_deals(complete, constants))

        deals = sorted(
            {deal.broker_deal_id: deal for deal in enriched}.values(),
            key=lambda deal: (deal.deal_time, int(deal.broker_deal_id)),
        )
        trades = reconstruct_closed_positions(deals)
        last_deal = deals[-1] if deals else None
        payload = {
            "trading_account_id": self.config.trading_account_id,
            "account": snapshot,
            "deals": [deal.to_dict() for deal in deals],
            "trades": [trade.to_dict() for trade in trades],
            "last_deal_time": last_deal.deal_time if last_deal else registered.get("last_deal_time"),
            "last_deal_ticket": last_deal.broker_deal_id if last_deal else registered.get("last_deal_ticket"),
            "sync_status": "connected",
            "sync_error": None,
        }
        result = self.api_client.send_sync(payload)
        log_event(
            logger,
            logging.INFO,
            "mt5_sync_completed",
            account_id=self.config.trading_account_id,
            deals_received=len(raw_deals),
            deals_normalized=len(deals),
            positions_reconstructed=len(trades),
            duration_ms=round((time.monotonic() - started) * 1000),
        )
        return result

    def report_failure(self, status: str, message: str) -> None:
        safe_message = message[:2000]
        self.api_client.send_sync({
            "trading_account_id": self.config.trading_account_id,
            "account": {"raw_metadata": {}},
            "deals": [],
            "trades": [],
            "last_deal_time": None,
            "last_deal_ticket": None,
            "sync_status": status,
            "sync_error": safe_message,
        })
