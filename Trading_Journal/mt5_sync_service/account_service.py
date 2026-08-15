from __future__ import annotations

from typing import Any

from .config import Config
from .mt5_client import Mt5Client, Mt5Error


class AccountService:
    def __init__(self, client: Mt5Client, config: Config) -> None:
        self.client = client
        self.config = config

    def verified_snapshot(self) -> dict[str, Any]:
        info = self.client.account_info()
        if int(info.get("login", 0)) != self.config.mt5_login:
            raise Mt5Error("Connected terminal returned an unexpected account login")
        server = str(info.get("server", ""))
        if server and server.casefold() != self.config.mt5_server.casefold():
            raise Mt5Error("Connected terminal returned an unexpected broker server")
        return {
            "login": str(info.get("login", "")),
            "name": info.get("name"),
            "server": server,
            "currency": info.get("currency"),
            "leverage": info.get("leverage"),
            "balance": info.get("balance"),
            "equity": info.get("equity"),
            "margin": info.get("margin"),
            "free_margin": info.get("margin_free"),
            "raw_metadata": {
                key: info.get(key)
                for key in ("company", "trade_mode", "limit_orders", "margin_level")
                if key in info
            },
        }
