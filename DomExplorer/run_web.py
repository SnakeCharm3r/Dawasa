from __future__ import annotations

import os

from src.web import create_app

if __name__ == "__main__":
    host = os.getenv("WEB_HOST", "0.0.0.0")
    port = int(os.getenv("WEB_PORT", "5000"))
    debug = os.getenv("WEB_DEBUG", "0") == "1"

    app = create_app()
    print(f"DomExplorer web UI available at http://{host}:{port}")
    app.run(host=host, port=port, debug=debug)
