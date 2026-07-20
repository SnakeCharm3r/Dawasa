try:
    from twisted.internet import reactor
except ImportError:
    class _FallbackReactor:
        def run(self):
            print("Twisted not available; running without reactor loop.")

        def stop(self):
            pass

        @property
        def running(self):
            return False

    reactor = _FallbackReactor()

from src.api.client import CTraderClient
from src.auth.authentication import Authentication
from src.collectors.symbols import SymbolsCollector
from src.collectors.market_depth import MarketDepthCollector
from src.collectors.execution import ExecutionCollector
from src.services.event_bus import EventBus
from src.services.market_status import MarketStatusService
from src.collectors.statistics import StatisticsCollector
from src.analysis.analysis_listener import AnalysisListener
from src.analysis.order_flow import OrderFlowAnalyzer
from src.utils.console import DomMonitor
from src.storage.database import DatabaseManager
from src.storage.symbol_store import SymbolStore
from src.storage.tick_store import TickStore
from src.utils.logger import AppLogger
from src.config import config



class StartupController:


    def __init__(self):

        self.connection = CTraderClient()
        self.database = DatabaseManager()
        self.logger = AppLogger(db=self.database)
        self.event_bus = EventBus()
        self.symbol_store = SymbolStore()
        self.tick_store = TickStore(history_size=1000)
        self.market_depth = MarketDepthCollector(event_bus=self.event_bus)
        self.execution = ExecutionCollector(
            event_bus=self.event_bus,
            symbol_store=self.symbol_store,
        )
        self.statistics_collector = StatisticsCollector(self.tick_store)

        self.authentication = Authentication(
            self.connection.get_client()
        )


        # -------------------------------------------------
        # Authentication callback connection
        # -------------------------------------------------

        # -------------------------------------------------
        # Symbols collector
        # -------------------------------------------------

        self.symbols = SymbolsCollector(

            self.connection.get_client(),

            self.authentication.account_id,
            db=self.database,
            logger=self.logger,
            event_bus=self.event_bus,
            symbol_store=self.symbol_store,

        )

        focus_symbol = getattr(config, "FOCUS_SYMBOL", "XAUUSD")

        digits_by_symbol = {}
        focus_digits = getattr(config, "FOCUS_SYMBOL_DIGITS", None)
        if focus_digits is not None:
            digits_by_symbol[focus_symbol] = int(focus_digits)

        from src.utils.price import PriceNormalizer

        self._normalizer = PriceNormalizer(digits_by_symbol)

        # -------------------------------------------------
        # Analysis listener (event-driven, decoupled)
        # Consumes DepthUpdated events and persists metrics
        # -------------------------------------------------

        self.analysis_listener = AnalysisListener(
            event_bus=self.event_bus,
            db=self.database,
            symbol_store=self.symbol_store,
            logger=self.logger,
            normalizer=self._normalizer,
        )

        self.order_flow = OrderFlowAnalyzer(
            event_bus=self.event_bus,
            symbol=focus_symbol,
            whale_size=getattr(config, "ORDERFLOW_WHALE_SIZE", 500.0),
            absorption_volume=getattr(config, "ORDERFLOW_ABSORPTION_VOLUME", 300.0),
            spoof_size=getattr(config, "ORDERFLOW_SPOOF_SIZE", 500.0),
            spoof_window=getattr(config, "ORDERFLOW_SPOOF_WINDOW", 2.0),
            trade_window=getattr(config, "ORDERFLOW_TRADE_WINDOW", 5.0),
            logger=self.logger,
        )

        self.dom_monitor = DomMonitor(
            event_bus=self.event_bus,
            symbol=focus_symbol,
            digits_by_symbol=digits_by_symbol,
        )

        watch_symbols = [focus_symbol] + list(config.SUBSCRIBE_SYMBOLS)
        self.market_status = MarketStatusService(event_bus=self.event_bus, logger=self.logger)
        self.market_status.check(watch_symbols)
        self._market_status_thread = self._start_market_status_loop(watch_symbols)


        self.connection.handler.set_authentication(
            self.authentication
        )
        self.connection.handler.set_symbols_collector(
            self.symbols
        )
        self.connection.handler.set_tick_store(
            self.tick_store
        )
        self.connection.handler.set_statistics_collector(
            self.statistics_collector
        )
        self.connection.handler.set_market_depth_collector(
            self.market_depth
        )
        self.connection.handler.set_execution_collector(
            self.execution
        )
        self.connection.handler.set_symbol_store(
            self.symbol_store
        )
        self.connection.handler.set_focus_symbol(
            getattr(config, "FOCUS_SYMBOL", "XAUUSD")
        )



    def _start_market_status_loop(self, watch_symbols):
        """Poll market-open state in a daemon thread and emit change events."""
        from threading import Thread, Event

        stop_event = Event()

        def _run():
            while not stop_event.is_set():
                try:
                    self.market_status.check(watch_symbols)
                except Exception as exc:  # pragma: no cover - defensive
                    self.logger.error(f"Market status check failed: {exc}")
                # Check roughly every minute.
                stop_event.wait(60)

        thread = Thread(target=_run, daemon=True)
        thread.start()
        thread._stop_event = stop_event
        return thread


    def start(self):

        print("Initialising DomExplorer...")

        self.dom_monitor.start()

        self.connection.connect()


        print("Starting Twisted event loop...")


        try:

            reactor.run()


        except KeyboardInterrupt:

            self.dom_monitor.stop()

            print("\nStopping DomExplorer...")

            self.stop()



    def stop(self):

        print("Disconnecting...")

        self.dom_monitor.stop()

        if getattr(self, "_market_status_thread", None) is not None:
            self._market_status_thread._stop_event.set()

        self.connection.disconnect()


        if reactor.running:

            reactor.stop()


        print("Shutdown complete.")
