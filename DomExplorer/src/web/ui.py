from __future__ import annotations

from pathlib import Path


def ui_path() -> Path:
    """Absolute path to the built React dashboard assets."""
    return Path(__file__).resolve().parent.parent.parent / "webui" / "dist"
