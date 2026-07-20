from __future__ import annotations

from typing import Any, Optional

from flask import Flask, jsonify, request, send_from_directory

from src.config import config
from src.storage.database import DatabaseManager
from src.utils.price import build_normalizer_from_config
from src.services.market_status import MarketStatusService


def create_app(db: Optional[DatabaseManager] = None) -> Flask:
    app = Flask(__name__, static_folder=None)

    database = db or DatabaseManager()
    database.connect()
    normalizer = build_normalizer_from_config(config, getattr(config, "FOCUS_SYMBOL", "XAUUSD"))

    def _symbol_lookup(name: str) -> Optional[dict]:
        row = database.fetchone(
            "SELECT symbol_id, symbol_name, description FROM symbols WHERE symbol_name = ?",
            (name.upper(),),
        )
        return row

    @app.route("/api/symbols")
    def api_symbols() -> Any:
        rows = database.fetchall(
            "SELECT symbol_id, symbol_name, description FROM symbols WHERE enabled = 1 ORDER BY symbol_name"
        )
        return jsonify(rows)

    @app.route("/api/symbols/<symbol>/latest")
    def api_latest(symbol: str) -> Any:
        sym = _symbol_lookup(symbol)
        if sym is None:
            return jsonify({"error": "symbol not found"}), 404
        metrics = database.get_latest_metrics(sym["symbol_id"])
        return jsonify({
            "symbol": sym["symbol_name"],
            "symbol_id": sym["symbol_id"],
            "metrics": {m["metric_name"]: m["metric_value"] for m in metrics},
            "updated_at": metrics[0]["timestamp"] if metrics else None,
        })

    @app.route("/api/symbols/<symbol>/metrics/<metric>")
    def api_metric_series(symbol: str, metric: str) -> Any:
        sym = _symbol_lookup(symbol)
        if sym is None:
            return jsonify({"error": "symbol not found"}), 404
        try:
            limit = min(int(request.args.get("limit", 500)), 5000)
        except (TypeError, ValueError):
            limit = 500
        rows = database.get_metric_series(sym["symbol_id"], metric, limit)
        rows.reverse()
        return jsonify({
            "symbol": sym["symbol_name"],
            "metric": metric,
            "series": [{"value": r["value"], "timestamp": r["timestamp"]} for r in rows],
        })

    @app.route("/api/symbols/<symbol>/depth")
    def api_depth(symbol: str) -> Any:
        sym = _symbol_lookup(symbol)
        if sym is None:
            return jsonify({"error": "symbol not found"}), 404
        rows = database.get_depth_snapshots(sym["symbol_id"])
        # Prices are stored raw (integer-scaled); normalize for display.
        for r in rows:
            r["price"] = normalizer.normalize(r["price"], sym["symbol_name"]) or r["price"]
        bids = [r for r in rows if r["side"] == "bid"]
        asks = [r for r in rows if r["side"] == "ask"]
        bids.sort(key=lambda r: r["price"], reverse=True)
        asks.sort(key=lambda r: r["price"])
        return jsonify({
            "symbol": sym["symbol_name"],
            "bids": bids,
            "asks": asks,
        })

    @app.route("/api/logs")
    def api_logs() -> Any:
        try:
            limit = min(int(request.args.get("limit", 200)), 1000)
        except (TypeError, ValueError):
            limit = 200
        level = request.args.get("level")
        if level:
            rows = database.fetchall(
                "SELECT level, message, created_at FROM application_logs WHERE level = ? ORDER BY id DESC LIMIT ?",
                (level.upper(), limit),
            )
        else:
            rows = database.fetchall(
                "SELECT level, message, created_at FROM application_logs ORDER BY id DESC LIMIT ?",
                (limit,),
            )
        rows.reverse()
        return jsonify(rows)

    @app.route("/api/health")
    def api_health() -> Any:
        return jsonify({"status": "ok", "focus_symbol": getattr(config, "FOCUS_SYMBOL", "XAUUSD")})

    @app.route("/api/market-status")
    def api_market_status() -> Any:
        watch = [getattr(config, "FOCUS_SYMBOL", "XAUUSD")] + list(config.SUBSCRIBE_SYMBOLS)
        status = MarketStatusService().check(watch)
        return jsonify(status)

    @app.route("/", defaults={"path": "index.html"})
    @app.route("/<path:path>")
    def serve_ui(path: str) -> Any:
        from src.web.ui import ui_path

        return send_from_directory(ui_path(), path)

    return app
