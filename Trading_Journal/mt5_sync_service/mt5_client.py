from __future__ import annotations

import importlib
from datetime import datetime
from typing import Any

from .config import Config


class Mt5Error(RuntimeError):
    pass


class Mt5Client:
    """Minimal read-only wrapper around the official MetaTrader5 package."""

    def __init__(self, config: Config, module: Any | None = None) -> None:
        self.config = config
        self.mt5 = module

    def _module(self) -> Any:
        if self.mt5 is None:
            try:
                self.mt5 = importlib.import_module("MetaTrader5")
            except ImportError as exc:
                raise Mt5Error(
                    "MetaTrader5 is not installed. Run this service on the Windows/VPS host that runs MT5."
                ) from exc
        return self.mt5

    def connect(self) -> None:
        mt5 = self._module()
        initialized = mt5.initialize(
            path=self.config.mt5_terminal_path,
            login=self.config.mt5_login,
            password=self.config.mt5_password,
            server=self.config.mt5_server,
        )
        if not initialized:
            raise Mt5Error(f"MT5 initialize failed: {mt5.last_error()}")
        info = self.account_info()
        if int(info.get("login", 0)) != self.config.mt5_login:
            self.shutdown()
            raise Mt5Error("The connected MT5 account does not match MT5_LOGIN")

    def account_info(self) -> dict[str, Any]:
        mt5 = self._module()
        info = mt5.account_info()
        if info is None:
            raise Mt5Error(f"MT5 account_info failed: {mt5.last_error()}")
        return self._as_dict(info)

    def history_deals(self, date_from: datetime, date_to: datetime) -> list[Any]:
        mt5 = self._module()
        deals = mt5.history_deals_get(date_from, date_to)
        if deals is None:
            raise Mt5Error(f"MT5 history_deals_get failed: {mt5.last_error()}")
        return list(deals)

    def history_deals_for_position(self, position_id: int) -> list[Any]:
        mt5 = self._module()
        deals = mt5.history_deals_get(position=position_id)
        if deals is None:
            raise Mt5Error(
                f"MT5 history_deals_get(position={position_id}) failed: {mt5.last_error()}"
            )
        return list(deals)

    def constants(self) -> dict[str, int]:
        mt5 = self._module()
        names = (
            "DEAL_ENTRY_IN",
            "DEAL_ENTRY_OUT",
            "DEAL_ENTRY_INOUT",
            "DEAL_ENTRY_OUT_BY",
            "DEAL_TYPE_BUY",
            "DEAL_TYPE_SELL",
        )
        return {name: int(getattr(mt5, name)) for name in names}

    def shutdown(self) -> None:
        if self.mt5 is not None:
            self.mt5.shutdown()

    @staticmethod
    def _as_dict(value: Any) -> dict[str, Any]:
        if hasattr(value, "_asdict"):
            return dict(value._asdict())
        if isinstance(value, dict):
            return dict(value)
        raise Mt5Error(f"Unexpected MT5 response type: {type(value).__name__}")
