"""Generate official Funity Android launcher / splash from Brand Identity System."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

PRIMARY = (79, 70, 229, 255)  # #4F46E5
ACCENT = (249, 115, 22, 255)  # #F97316
SUCCESS = (16, 185, 129, 255)  # #10B981
WHITE = (255, 255, 255, 255)

ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "assets" / "branding"
RES = ROOT / "android" / "app" / "src" / "main" / "res"

OFFICIAL_SVG = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none" role="img" aria-label="Funity">
  <circle cx="38" cy="50" r="22" stroke="#4F46E5" stroke-width="10" fill="none"/>
  <circle cx="62" cy="50" r="22" stroke="#F97316" stroke-width="10" fill="none"/>
  <path d="M 50 32 C 43 32 38 37 38 44 C 38 54 50 70 50 70 C 50 70 62 54 62 44 C 62 37 57 32 50 32 Z" fill="#10B981"/>
  <circle cx="50" cy="44" r="3.5" fill="#FFFFFF"/>
</svg>
"""


def _cubic(p0, p1, p2, p3, t):
    u = 1 - t
    return (
        u**3 * p0[0] + 3 * u**2 * t * p1[0] + 3 * u * t**2 * p2[0] + t**3 * p3[0],
        u**3 * p0[1] + 3 * u**2 * t * p1[1] + 3 * u * t**2 * p2[1] + t**3 * p3[1],
    )


def draw_mark(size: int, *, app_icon: bool) -> Image.Image:
    """Brand Identity System mark.

    app_icon: indigo plate + white/orange rings (Mobile App Icon Variant).
    else: transparent + indigo/orange rings (API / favicon mark).
    """
    img = Image.new("RGBA", (size, size), PRIMARY if app_icon else (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    s = size / 100.0

    def xy(x: float, y: float) -> tuple[float, float]:
        return x * s, y * s

    def circ(cx: float, cy: float, r: float, outline, width: float) -> None:
        x0, y0 = xy(cx - r, cy - r)
        x1, y1 = xy(cx + r, cy + r)
        draw.ellipse(
            [x0, y0, x1, y1],
            outline=outline,
            width=max(1, int(round(width * s))),
        )

    circ(38, 50, 22, WHITE if app_icon else PRIMARY, 10)
    circ(62, 50, 22, ACCENT, 10)

    pts: list[tuple[float, float]] = []
    for t in [i / 24 for i in range(25)]:
        pts.append(_cubic((50, 32), (43, 32), (38, 37), (38, 44), t))
    for t in [i / 24 for i in range(1, 25)]:
        pts.append(_cubic((38, 44), (38, 54), (50, 70), (50, 70), t))
    for t in [i / 24 for i in range(1, 25)]:
        pts.append(_cubic((50, 70), (50, 70), (62, 54), (62, 44), t))
    for t in [i / 24 for i in range(1, 25)]:
        pts.append(_cubic((62, 44), (62, 37), (57, 32), (50, 32), t))

    draw.polygon([xy(x, y) for x, y in pts], fill=SUCCESS)

    hr = 3.5
    x0, y0 = xy(50 - hr, 44 - hr)
    x1, y1 = xy(50 + hr, 44 + hr)
    draw.ellipse([x0, y0, x1, y1], fill=PRIMARY if app_icon else WHITE)
    return img


def main() -> None:
    ASSETS.mkdir(parents=True, exist_ok=True)
    master_app = draw_mark(1024, app_icon=True)
    master_mark = draw_mark(1024, app_icon=False)
    master_app.save(ASSETS / "funity-app-icon.png")
    master_mark.save(ASSETS / "funity-mark.png")
    (ASSETS / "funity-mark.svg").write_text(OFFICIAL_SVG, encoding="utf-8")

    densities = {
        "mipmap-mdpi": 48,
        "mipmap-hdpi": 72,
        "mipmap-xhdpi": 96,
        "mipmap-xxhdpi": 144,
        "mipmap-xxxhdpi": 192,
    }
    # Splash mark: transparent plate, white/orange rings (sits on indigo launch bg).
    splash_mark = draw_mark(1024, app_icon=True)
    # Punch plate to transparent so launch_background color shows once.
    pixels = splash_mark.load()
    w, h = splash_mark.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = pixels[x, y]
            if (r, g, b) == PRIMARY[:3]:
                pixels[x, y] = (0, 0, 0, 0)

    for folder, px in densities.items():
        out_dir = RES / folder
        out_dir.mkdir(parents=True, exist_ok=True)
        master_app.resize((px, px), Image.Resampling.LANCZOS).save(
            out_dir / "ic_launcher.png"
        )
        splash_mark.resize((px * 3, px * 3), Image.Resampling.LANCZOS).save(
            out_dir / "launch_image.png"
        )

    fg_size = 432
    inset = int(fg_size * 0.18)
    fg = Image.new("RGBA", (fg_size, fg_size), (0, 0, 0, 0))
    inner = draw_mark(fg_size - 2 * inset, app_icon=True)
    fg.paste(inner, (inset, inset), inner)
    drawable = RES / "drawable"
    drawable.mkdir(parents=True, exist_ok=True)
    fg.save(drawable / "ic_launcher_foreground.png")

    adaptive = RES / "mipmap-anydpi-v26"
    adaptive.mkdir(parents=True, exist_ok=True)
    (adaptive / "ic_launcher.xml").write_text(
        """<?xml version="1.0" encoding="utf-8"?>
<adaptive-icon xmlns:android="http://schemas.android.com/apk/res/android">
    <background android:drawable="@color/ic_launcher_background"/>
    <foreground android:drawable="@drawable/ic_launcher_foreground"/>
</adaptive-icon>
""",
        encoding="utf-8",
    )

    values = RES / "values"
    values.mkdir(parents=True, exist_ok=True)
    colors = values / "colors.xml"
    if colors.exists():
        text = colors.read_text(encoding="utf-8")
        if "ic_launcher_background" not in text:
            text = text.replace(
                "</resources>",
                '    <color name="ic_launcher_background">#4F46E5</color>\n</resources>',
            )
            colors.write_text(text, encoding="utf-8")
    else:
        colors.write_text(
            """<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="ic_launcher_background">#4F46E5</color>
</resources>
""",
            encoding="utf-8",
        )

    print("OK", ASSETS / "funity-app-icon.png")


if __name__ == "__main__":
    main()
