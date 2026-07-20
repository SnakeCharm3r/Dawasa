import os
import tempfile
import unittest
from types import SimpleNamespace

from src.storage.database import DatabaseManager


class DatabaseManagerTests(unittest.TestCase):
    def test_sqlite_bootstrap_and_upsert_symbol(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            db_path = os.path.join(tmp_dir, "test.sqlite3")
            config = SimpleNamespace(
                DB_HOST="localhost",
                DB_PORT=3306,
                DB_USER="root",
                DB_PASSWORD="",
                DB_NAME="domexplorer",
                DB_PATH=db_path,
            )
            db = DatabaseManager(config)

            db.connect()
            db.upsert_symbol(
                {
                    "symbol_id": 1,
                    "symbol_name": "EURUSD",
                    "description": "Euro vs Dollar",
                    "base_asset_id": 1,
                    "quote_asset_id": 2,
                    "category_id": 10,
                    "enabled": True,
                }
            )

            rows = db.fetchall("SELECT symbol_id, symbol_name FROM symbols")

            self.assertEqual(len(rows), 1)
            self.assertEqual(rows[0]["symbol_name"], "EURUSD")
