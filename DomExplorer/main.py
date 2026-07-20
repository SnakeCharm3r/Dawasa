import sys

try:
    import ctrader_open_api  # noqa: F401
except ImportError:
    sys.stderr.write(
        "\nERROR: ctrader_open_api is not installed. DomExplorer needs it to pull "
        "market data.\nActivate the virtual environment first, e.g.:\n"
        "    source venv/bin/activate\n"
        "    python main.py\n\n"
        "Without it the app runs in offline mode and collects nothing.\n\n"
    )
    sys.exit(1)

from src.controllers.startup import StartupController


def main():

    print("==========================")
    print(" DomExplorer Started")
    print("==========================")

    app = StartupController()
    app.start()


if __name__ == "__main__":
    main()