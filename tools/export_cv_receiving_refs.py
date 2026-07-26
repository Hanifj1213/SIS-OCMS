"""
Re-export Control Valve RECEIVING sheets without cropping.
Uses Excel ExportAsFixedFormat(IgnorePrintAreas=True) + A3 fit-to-width.
"""
from __future__ import annotations

import json
import shutil
import tempfile
from pathlib import Path

import fitz
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "public" / "images" / "inspection"
COMPLETED = (
    ROOT
    / "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY"
    / "POWERTRAIN"
    / "CHECKSHEET CONTROL VALVE POWERTRAIN ALL UNIT PT 2026"
)
MANIFEST = ROOT / "database" / "data" / "control_valve_ref_pages.json"

JOBS = {
    "d155-6": ("cs cv pm d155-6(COMPLETED).xlsx", "receiving"),
    "d375-6": ("cs cv pm d375-6(COMPLETED).xlsx", "receiving"),
    "hd785-7": ("cs cv tf hd785-7(COMPLETED).xlsx", "RECEIVING"),
    "gd825a-2": ("cs cv tm gd825-2(COMPLETED).xlsx", "RECEIVING"),
    "wa800-3": ("cs cv tm wa800-3(COMPLETED).xlsx", "RECEIVING"),
}


def excel_receiving_to_pdf(xlsx: Path, sheet_name: str, pdf_out: Path) -> None:
    import win32com.client  # type: ignore

    excel = win32com.client.DispatchEx("Excel.Application")
    excel.Visible = False
    excel.DisplayAlerts = False
    excel.ScreenUpdating = False
    try:
        wb = excel.Workbooks.Open(str(xlsx.resolve()), True)
        try:
            target = None
            for ws in wb.Worksheets:
                if str(ws.Name).strip().lower() == sheet_name.strip().lower():
                    target = ws
                    break
            if target is None:
                raise RuntimeError(f"sheet {sheet_name!r} not found")

            for ws in wb.Worksheets:
                ws.Visible = -1 if ws.Name == target.Name else 0

            target.Activate()
            target.PageSetup.PrintArea = ""
            ps = target.PageSetup
            ps.Orientation = 1  # portrait
            ps.PaperSize = 8  # A3
            ps.Zoom = False
            ps.FitToPagesWide = 1
            try:
                ps.FitToPagesTall = 1
            except Exception:
                pass
            ps.LeftMargin = excel.InchesToPoints(0.2)
            ps.RightMargin = excel.InchesToPoints(0.2)
            ps.TopMargin = excel.InchesToPoints(0.2)
            ps.BottomMargin = excel.InchesToPoints(0.2)

            if pdf_out.exists():
                pdf_out.unlink()
            # Type=PDF, Quality=Standard, IncludeDocProperties=True, IgnorePrintAreas=True
            wb.ExportAsFixedFormat(0, str(pdf_out.resolve()), 0, True, True)
        finally:
            wb.Close(False)
    finally:
        excel.Quit()


def render_pdf_pages(pdf: Path, egi: str, zoom: float = 2.2) -> list[str]:
    dest_dir = OUT / egi
    dest_dir.mkdir(parents=True, exist_ok=True)
    for old in dest_dir.glob("control-valve*.png"):
        old.unlink()

    doc = fitz.open(pdf)
    paths: list[str] = []
    try:
        for i, page in enumerate(doc):
            pix = page.get_pixmap(matrix=fitz.Matrix(zoom, zoom), alpha=False)
            img = Image.frombytes("RGB", (pix.width, pix.height), pix.samples)
            max_side = 2200
            w, h = img.size
            if max(w, h) > max_side:
                scale = max_side / max(w, h)
                img = img.resize((int(w * scale), int(h * scale)), Image.Resampling.LANCZOS)
            name = "control-valve.png" if i == 0 else f"control-valve-p{i + 1:02d}.png"
            dest = dest_dir / name
            img.save(dest, format="PNG", optimize=True)
            paths.append(f"/images/inspection/{egi}/{name}")
            print(f"  page {i + 1}: {dest.name} {dest.stat().st_size}B {img.size}")
    finally:
        doc.close()
    return paths


def main() -> None:
    tmp = Path(tempfile.mkdtemp(prefix="cv_recv_full_"))
    manifest: dict[str, list[str]] = {}
    try:
        for egi, (filename, sheet) in JOBS.items():
            xlsx = COMPLETED / filename
            if not xlsx.exists():
                print(f"[skip] {egi}: missing {filename}")
                continue
            pdf = tmp / f"{egi}.pdf"
            print(f"[{egi}] Excel->PDF {filename} / {sheet}")
            try:
                excel_receiving_to_pdf(xlsx, sheet, pdf)
            except Exception as e:
                print(f"  FAIL export: {e}")
                continue
            print(f"  pdf {pdf.stat().st_size}B")
            pages = render_pdf_pages(pdf, egi)
            manifest[egi] = pages
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    MANIFEST.write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    print(f"Manifest: {MANIFEST.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
