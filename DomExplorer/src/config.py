import os

try:
    from dotenv import load_dotenv
except ImportError:
    def load_dotenv():
        return False


load_dotenv()


class Config:
    APP_NAME = os.getenv("APP_NAME", "DomExplorer")
    LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO")

    CTRADER_CLIENT_ID = os.getenv("CTRADER_CLIENT_ID")
    CTRADER_CLIENT_SECRET = os.getenv("CTRADER_CLIENT_SECRET")
    CTRADER_REDIRECT_URI = os.getenv("CTRADER_REDIRECT_URI")

    CTRADER_ACCESS_TOKEN = os.getenv("CTRADER_ACCESS_TOKEN")
    CTRADER_REFRESH_TOKEN = os.getenv("CTRADER_REFRESH_TOKEN")

    CTRADER_USER_ID = os.getenv("CTRADER_USER_ID")
    CTRADER_ACCOUNT_ID = os.getenv("CTRADER_ACCOUNT_ID")

    DB_HOST = os.getenv("DB_HOST", "localhost")
    DB_PORT = int(os.getenv("DB_PORT", "3306"))
    DB_USER = os.getenv("DB_USER", "root")
    DB_PASSWORD = os.getenv("DB_PASSWORD", "")
    DB_NAME = os.getenv("DB_NAME", "domexplorer")
    DB_PATH = os.getenv("DB_PATH", "domexplorer.sqlite3")

    HISTORY_SIZE = int(os.getenv("HISTORY_SIZE", "1000"))
    MAX_SUBSCRIPTIONS = int(os.getenv("MAX_SUBSCRIPTIONS", "100"))

    FOCUS_SYMBOL = os.getenv("FOCUS_SYMBOL", "XAUUSD")
    FOCUS_SYMBOL_DIGITS = int(os.getenv("FOCUS_SYMBOL_DIGITS", "0"))

    # Per-symbol price precision as "SYM1:digits,SYM2:digits" (e.g. "XAUUSD:2,GBPUSD:5,GBPCHF:5").
    SYMBOL_DIGITS = os.getenv("SYMBOL_DIGITS", "")
    SUBSCRIBE_SYMBOLS = [s.strip().upper() for s in os.getenv("SUBSCRIBE_SYMBOLS", "").split(",") if s.strip()]

    # Order-flow analytics thresholds (raw cTrader volumes, pre-lot-size)
    ORDERFLOW_WHALE_SIZE = float(os.getenv("ORDERFLOW_WHALE_SIZE", "500"))
    ORDERFLOW_ABSORPTION_VOLUME = float(os.getenv("ORDERFLOW_ABSORPTION_VOLUME", "300"))
    ORDERFLOW_SPOOF_SIZE = float(os.getenv("ORDERFLOW_SPOOF_SIZE", "500"))
    ORDERFLOW_SPOOF_WINDOW = float(os.getenv("ORDERFLOW_SPOOF_WINDOW", "2.0"))
    ORDERFLOW_TRADE_WINDOW = float(os.getenv("ORDERFLOW_TRADE_WINDOW", "5.0"))

    # Market session configuration. Forex/metal sessions are closed over the
    # weekend (Sat 00:00 - Sun 23:59 local time) and outside the daily window
    # below. Crypto (e.g. BTCUSD) trades 24/7 so it is excluded from the
    # weekend-close rule via MARKET_ALWAYS_OPEN_SYMBOLS.
    MARKET_OPEN_HOUR = int(os.getenv("MARKET_OPEN_HOUR", "0"))
    MARKET_CLOSE_HOUR = int(os.getenv("MARKET_CLOSE_HOUR", "24"))
    MARKET_ALWAYS_OPEN_SYMBOLS = [
        s.strip().upper()
        for s in os.getenv("MARKET_ALWAYS_OPEN_SYMBOLS", "BTCUSD").split(",")
        if s.strip()
    ]


config = Config()
