"""Generate iOS AppIcon set from official Funity app icon."""

from __future__ import annotations

import json
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
SRC = Image.open(ROOT / "assets" / "branding" / "funity-app-icon.png")
IOS = ROOT / "ios" / "Runner" / "Assets.xcassets" / "AppIcon.appiconset"


def main() -> None:
    meta_path = IOS / "Contents.json"
    meta = json.loads(meta_path.read_text(encoding="utf-8"))
    for img in meta.get("images", []):
        scale = int(str(img.get("scale", "1x")).replace("x", ""))
        size = float(img["size"].split("x")[0])
        px = int(round(size * scale))
        filename = img.get("filename") or f"Icon-App-{img['size']}@{img['scale']}.png"
        img["filename"] = filename
        SRC.resize((px, px), Image.Resampling.LANCZOS).save(IOS / filename)
    meta_path.write_text(json.dumps(meta, indent=2) + "\n", encoding="utf-8")
    print("iOS icons:", len(meta.get("images", [])))


if __name__ == "__main__":
    main()
