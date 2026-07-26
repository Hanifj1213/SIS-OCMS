"""
Extract Control Valve receiving reference PNGs into:
  public/images/inspection/{egi}/control-valve.png

Prefer full-page PDF render (matches Engine/Final Drive style),
fallback to largest embedded image from xlsx.
"""
from __future__ import annotations

import io
import zipfile
from pathlib import Path

import fitz
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
OUT_ROOT = ROOT / "public" / "images" / "inspection"
PT = ROOT / "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY" / "POWERTRAIN"
KOMP = ROOT / "002. CHECKSHEET KOMPONEN"

# Prefer PDF (full receiving sheet diagram). Fallback xlsx if needed.
SOURCES: dict[str, list[Path]] = {
    "d155-6": [
        PT / "D 155-6" / "CONTROL VALVE" / "CONTROL VALVE D155 -6.pdf",
        PT / "D 155-6" / "CONTROL VALVE" / "CONTROL VALVE D155 -6.xlsx",
    ],
    "d375-6": [
        PT / "DZ 375-6" / "CONTROL VALVE" / "CONTROL VALVE D 375 - 6.pdf",
        PT / "DZ 375-6" / "CONTROL VALVE" / "CONTROL VALVE D 375 - 6.xlsx",
        PT / "DZ 375-5" / "CONTROL VALVE" / "CONTROL VALVE D 375 - 5.pdf",
    ],
    "hd785-7": [
        PT / "HD 785-7" / "CONTROL VALVE" / "CONTROL VALVE HD 785 - 7.pdf",
        PT / "HD 785-7" / "CONTROL VALVE" / "CONTROL VALVE HD 785 - 7 (2).pdf",
        PT / "HD 785-7" / "CONTROL VALVE" / "CONTROL VALVE HD 785 - 7.xlsx",
    ],
    "gd825a-2": [
        PT / "GD 825-2" / "CONTROL VALVE" / "CONTROL VALVE GD 825.pdf",
        PT / "GD 825-2" / "CONTROL VALVE" / "CONTROL VALVE GD 825.xlsx",
    ],
    "wa800-3": [
        KOMP / "10. WA800-3" / "POWERTRAIN" / "TORQEU FLOW -TRANSMISSION" / "COVER CONTROL VALVE CHECKSHEET.pdf",
        PT / "_SIAP_UPLOAD_GSHEET" / "Control Valve" / "WA800-3" / "RECEIVING Control Valve WA800-3.xlsx",
        PT / "WA 800-3" / "CONTROL VALVE" / "013_CONTROL VALVE WA800-3.xls",
    ],
}

# Skip if already present and reasonably large (PC1250/PC2000 already done)
MIN_EXISTING = 80_000


def page_to_png(pdf: Path, page_index: int = 0, zoom: float = 2.5) -> Image.Image | None:
    """Render one PDF page (default first = receiving sheet)."""
    doc = fitz.open(pdf)
    try:
        if len(doc) == 0:
            return None
        if page_index >= len(doc):
            page_index = 0
        pix = doc[page_index].get_pixmap(matrix=fitz.Matrix(zoom, zoom), alpha=False)
        mode = "RGB" if pix.n >= 3 else "L"
        img = Image.frombytes(mode, (pix.width, pix.height), pix.samples)
        if mode == "L":
            img = img.convert("RGB")
        return img
    finally:
        doc.close()


def largest_xlsx_image(xlsx: Path) -> Image.Image | None:
    if xlsx.suffix.lower() not in {".xlsx"}:
        return None
    best: Image.Image | None = None
    best_area = 0
    with zipfile.ZipFile(xlsx) as zf:
        for name in zf.namelist():
            if not name.startswith("xl/media/"):
                continue
            raw = zf.read(name)
            try:
                img = Image.open(io.BytesIO(raw)).convert("RGB")
            except Exception:
                continue
            area = img.width * img.height
            # Skip tiny logos/icons
            if area < 80_000:
                continue
            if area > best_area:
                best_area = area
                best = img
    return best


def largest_pdf_embedded(pdf: Path) -> Image.Image | None:
    doc = fitz.open(pdf)
    try:
        best = None
        best_area = 0
        for page in doc:
            for img in page.get_images(full=True):
                xref = img[0]
                try:
                    pix = fitz.Pixmap(doc, xref)
                    if pix.n >= 5:  # CMYK
                        pix = fitz.Pixmap(fitz.csRGB, pix)
                    area = pix.width * pix.height
                    if area < 80_000:
                        continue
                    mode = "RGB" if pix.n >= 3 else "L"
                    im = Image.frombytes(mode, (pix.width, pix.height), pix.samples)
                    if mode == "L":
                        im = im.convert("RGB")
                    if area > best_area:
                        best_area = area
                        best = im
                except Exception:
                    continue
        return best
    finally:
        doc.close()


def save_png(img: Image.Image, dest: Path) -> None:
    dest.parent.mkdir(parents=True, exist_ok=True)
    # Cap very large pages so UI stays snappy, keep quality
    max_side = 1800
    w, h = img.size
    if max(w, h) > max_side:
        scale = max_side / max(w, h)
        img = img.resize((int(w * scale), int(h * scale)), Image.Resampling.LANCZOS)
    img.save(dest, format="PNG", optimize=True)


def main() -> None:
    for egi, sources in SOURCES.items():
        dest = OUT_ROOT / egi / "control-valve.png"
        if dest.exists() and dest.stat().st_size >= MIN_EXISTING:
            print(f"[skip] {egi}: already has {dest.name} ({dest.stat().st_size} bytes)")
            continue

        chosen = None
        chosen_src = None
        method = None

        for src in sources:
            if not src.exists():
                print(f"  miss source: {src}")
                continue

            img = None
            if src.suffix.lower() == ".pdf":
                # Full-page render looks most like Engine reference sheets
                img = page_to_png(src, zoom=2.2)
                method = "pdf-page-render"
                if img is None or (img.width * img.height) < 200_000:
                    emb = largest_pdf_embedded(src)
                    if emb is not None:
                        img = emb
                        method = "pdf-embedded"
            elif src.suffix.lower() == ".xlsx":
                img = largest_xlsx_image(src)
                method = "xlsx-media"

            if img is None:
                continue

            # Prefer larger / more diagram-like
            if chosen is None or (img.width * img.height) > (chosen.width * chosen.height):
                chosen = img
                chosen_src = src
                # For PDF page render, take first good hit (usually receiving page)
                if method == "pdf-page-render" and (img.width * img.height) >= 500_000:
                    break

        if chosen is None:
            print(f"[FAIL] {egi}: no usable image")
            continue

        save_png(chosen, dest)
        print(
            f"[ok] {egi}: {dest.relative_to(ROOT)} "
            f"{dest.stat().st_size} bytes ({chosen.width}x{chosen.height}) "
            f"via {method} <- {chosen_src.name}"
        )


if __name__ == "__main__":
    main()
