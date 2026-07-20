import os
import sqlite3
from contextlib import contextmanager
from pathlib import Path
from typing import Any, Iterable, Optional, Sequence

from src.config import config


class DatabaseManager:
    """Reusable database access layer with MySQL-first initialization and SQLite fallback."""

    def __init__(self, config_data: Optional[object] = None):
        self.config_data = config_data or config
        self._connection: Optional[Any] = None
        self._connection_type = "sqlite"

    def connect(self) -> Any:
        if self._connection is not None:
            return self._connection

        try:
            import mysql.connector as mysql_connector  # type: ignore
        except ImportError:
            mysql_connector = None

        if mysql_connector is not None:
            self._connection = mysql_connector.connect(
                host=self.config_data.DB_HOST,
                port=int(getattr(self.config_data, "DB_PORT", 3306)),
                user=self.config_data.DB_USER,
                password=self.config_data.DB_PASSWORD,
                database=self.config_data.DB_NAME,
                autocommit=False,
            )
            self._connection_type = "mysql"
        else:
            db_path = getattr(self.config_data, "DB_PATH", "domexplorer.sqlite3")
            self._connection = sqlite3.connect(db_path, check_same_thread=False)
            self._connection.row_factory = sqlite3.Row
            self._connection_type = "sqlite"

        self._connection.execute("PRAGMA foreign_keys = ON") if self._connection_type == "sqlite" else None
        self.initialize_tables()
        return self._connection

    def initialize_tables(self) -> None:
        sql_filename = "mysql_init.sql" if self._connection_type == "mysql" else "sqlite_init.sql"
        sql_path = Path(__file__).resolve().parent / "sql" / sql_filename
        if not sql_path.exists():
            return

        sql_text = sql_path.read_text(encoding="utf-8")
        self.execute_script(sql_text)

    def execute_script(self, sql_text: str) -> None:
        connection = self.connect()
        cursor = connection.cursor()
        try:
            cursor.executescript(sql_text) if self._connection_type == "sqlite" else cursor.execute(sql_text)
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            cursor.close()

    def execute(self, query: str, params: Optional[Sequence[Any]] = None) -> Any:
        connection = self.connect()
        cursor = connection.cursor()
        try:
            adapted_query, adapted_params = self._adapt_query(query, params or ())
            cursor.execute(adapted_query, adapted_params)
            connection.commit()
            return cursor
        except Exception:
            connection.rollback()
            raise
        finally:
            cursor.close()

    def executemany(self, query: str, params: Iterable[Sequence[Any]]) -> Any:
        connection = self.connect()
        cursor = connection.cursor()
        try:
            adapted_query, _ = self._adapt_query(query, ())
            cursor.executemany(adapted_query, list(params))
            connection.commit()
            return cursor
        except Exception:
            connection.rollback()
            raise
        finally:
            cursor.close()

    def fetchone(self, query: str, params: Optional[Sequence[Any]] = None) -> Optional[dict]:
        connection = self.connect()
        cursor = connection.cursor()
        try:
            adapted_query, adapted_params = self._adapt_query(query, params or ())
            cursor.execute(adapted_query, adapted_params)
            row = cursor.fetchone()
            if row is None:
                return None
            return dict(row) if hasattr(row, "keys") else row
        finally:
            cursor.close()

    def fetchall(self, query: str, params: Optional[Sequence[Any]] = None) -> list[dict]:
        connection = self.connect()
        cursor = connection.cursor()
        try:
            adapted_query, adapted_params = self._adapt_query(query, params or ())
            cursor.execute(adapted_query, adapted_params)
            rows = cursor.fetchall()
            return [dict(row) if hasattr(row, "keys") else row for row in rows]
        finally:
            cursor.close()

    @contextmanager
    def transaction(self):
        connection = self.connect()
        cursor = connection.cursor()
        try:
            yield cursor
            connection.commit()
        except Exception:
            connection.rollback()
            raise
        finally:
            cursor.close()

    def _adapt_query(self, query: str, params: Sequence[Any]) -> tuple[str, tuple[Any, ...]]:
        if self._connection_type == "sqlite":
            adapted_query = query.replace("%s", "?")
            adapted_query = adapted_query.replace("CURRENT_TIMESTAMP", "CURRENT_TIMESTAMP")
            return adapted_query, tuple(params)
        return query, tuple(params)

    def upsert_symbol(self, symbol_data: dict) -> None:
        if self._connection_type == "sqlite":
            self.execute(
                """
                INSERT INTO symbols (
                    symbol_id, symbol_name, description, base_asset_id, quote_asset_id, category_id, enabled, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT(symbol_id) DO UPDATE SET
                    symbol_name = excluded.symbol_name,
                    description = excluded.description,
                    base_asset_id = excluded.base_asset_id,
                    quote_asset_id = excluded.quote_asset_id,
                    category_id = excluded.category_id,
                    enabled = excluded.enabled,
                    updated_at = CURRENT_TIMESTAMP
                """,
                (
                    symbol_data.get("symbol_id"),
                    symbol_data.get("symbol_name"),
                    symbol_data.get("description"),
                    symbol_data.get("base_asset_id"),
                    symbol_data.get("quote_asset_id"),
                    symbol_data.get("category_id"),
                    symbol_data.get("enabled", True),
                ),
            )
            return

        self.execute(
            """
            INSERT INTO symbols (
                symbol_id, symbol_name, description, base_asset_id, quote_asset_id, category_id, enabled, created_at, updated_at
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                symbol_name = VALUES(symbol_name),
                description = VALUES(description),
                base_asset_id = VALUES(base_asset_id),
                quote_asset_id = VALUES(quote_asset_id),
                category_id = VALUES(category_id),
                enabled = VALUES(enabled),
                updated_at = CURRENT_TIMESTAMP
            """,
            (
                symbol_data.get("symbol_id"),
                symbol_data.get("symbol_name"),
                symbol_data.get("description"),
                symbol_data.get("base_asset_id"),
                symbol_data.get("quote_asset_id"),
                symbol_data.get("category_id"),
                symbol_data.get("enabled", True),
            ),
        )

    def upsert_category(self, category_data: dict) -> None:
        if self._connection_type == "sqlite":
            self.execute(
                """
                INSERT INTO symbol_categories (category_id, category_name)
                VALUES (?, ?)
                ON CONFLICT(category_id) DO UPDATE SET category_name = excluded.category_name
                """,
                (category_data.get("category_id"), category_data.get("category_name")),
            )
            return

        self.execute(
            """
            INSERT INTO symbol_categories (category_id, category_name)
            VALUES (%s, %s)
            ON DUPLICATE KEY UPDATE category_name = VALUES(category_name)
            """,
            (category_data.get("category_id"), category_data.get("category_name")),
        )

    def upsert_asset(self, asset_data: dict) -> None:
        if self._connection_type == "sqlite":
            self.execute(
                """
                INSERT INTO assets (asset_id, asset_name, asset_type)
                VALUES (?, ?, ?)
                ON CONFLICT(asset_id) DO UPDATE SET asset_name = excluded.asset_name, asset_type = excluded.asset_type
                """,
                (asset_data.get("asset_id"), asset_data.get("asset_name"), asset_data.get("asset_type")),
            )
            return

        self.execute(
            """
            INSERT INTO assets (asset_id, asset_name, asset_type)
            VALUES (%s, %s, %s)
            ON DUPLICATE KEY UPDATE asset_name = VALUES(asset_name), asset_type = VALUES(asset_type)
            """,
            (asset_data.get("asset_id"), asset_data.get("asset_name"), asset_data.get("asset_type")),
        )

    def upsert_metric(self, metric_data: dict) -> None:
        if self._connection_type == "sqlite":
            self.execute(
                """
                INSERT INTO metrics_history (symbol_id, metric_name, metric_value, timestamp)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                """,
                (
                    metric_data.get("symbol_id"),
                    metric_data.get("metric_name"),
                    metric_data.get("metric_value"),
                ),
            )
            return

        self.execute(
            """
            INSERT INTO metrics_history (symbol_id, metric_name, metric_value, timestamp)
            VALUES (%s, %s, %s, CURRENT_TIMESTAMP)
            """,
            (
                metric_data.get("symbol_id"),
                metric_data.get("metric_name"),
                metric_data.get("metric_value"),
            ),
        )

    def log(self, level: str, message: str) -> None:
        self.execute(
            "INSERT INTO application_logs (level, message, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)",
            (level, message),
        )

    def replace_depth_snapshots(self, symbol_id: int, levels: list[dict]) -> None:
        """Replace the stored ladder for a symbol (delete + bulk insert)."""
        if self._connection_type == "sqlite":
            self.execute(
                "DELETE FROM depth_snapshots WHERE symbol_id = ?",
                (symbol_id,),
            )
            if levels:
                self.executemany(
                    """
                    INSERT INTO depth_snapshots (symbol_id, side, quote_id, price, size)
                    VALUES (?, ?, ?, ?, ?)
                    """,
                    [
                        (symbol_id, lvl.get("side"), lvl.get("quote_id"), lvl.get("price"), lvl.get("size"))
                        for lvl in levels
                    ],
                )
            return

        self.execute("DELETE FROM depth_snapshots WHERE symbol_id = %s", (symbol_id,))
        if levels:
            self.executemany(
                """
                INSERT INTO depth_snapshots (symbol_id, side, quote_id, price, size)
                VALUES (%s, %s, %s, %s, %s)
                """,
                [
                    (symbol_id, lvl.get("side"), lvl.get("quote_id"), lvl.get("price"), lvl.get("size"))
                    for lvl in levels
                ],
            )

    def get_depth_snapshots(self, symbol_id: int) -> list[dict]:
        return self.fetchall(
            "SELECT side, quote_id, price, size FROM depth_snapshots WHERE symbol_id = ? ORDER BY price",
            (symbol_id,),
        )

    def get_latest_metrics(self, symbol_id: int) -> list[dict]:
        query = """
            SELECT m.metric_name, m.metric_value, m.timestamp
            FROM metrics_history m
            INNER JOIN (
                SELECT metric_name, MAX(id) AS max_id
                FROM metrics_history
                WHERE symbol_id = ?
                GROUP BY metric_name
            ) latest ON m.id = latest.max_id
            WHERE m.symbol_id = ?
        """
        return self.fetchall(query, (symbol_id, symbol_id))

    def get_metric_series(self, symbol_id: int, metric_name: str, limit: int = 500) -> list[dict]:
        return self.fetchall(
            """
            SELECT metric_value AS value, timestamp
            FROM metrics_history
            WHERE symbol_id = ? AND metric_name = ?
            ORDER BY id DESC
            LIMIT ?
            """,
            (symbol_id, metric_name, limit),
        )
