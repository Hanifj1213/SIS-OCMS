#!/usr/bin/env python3
"""
Bandingkan file .xlsx di _SIAP_UPLOAD_GSHEET dengan backup aslinya untuk
mendeteksi kerusakan format akibat proses tulis-ulang openpyxl.

Yang diperiksa langsung dari isi ZIP (bukan lewat openpyxl, supaya apa adanya):
  - gambar (xl/media/*)          : openpyxl membuang gambar saat save
  - drawing/chart/vml            : anchor gambar, komentar, shape
  - conditional formatting       : sering hilang
  - data validation              : bisa membengkak (1 objek per baris)
  - merged cells, printer setup  : layout cetak

Jalankan:
  python tools/inspect_xlsx_damage.py
  python tools/inspect_xlsx_damage.py --json tools/xlsx_damage.json
"""
from __future__ import annotations

import argparse
import json
import re
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CHECKSHEET = ROOT / "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY"
BACKUP_ROOT = CHECKSHEET / "_SIAP_BACKUP"

LIVE_DIRS = [
    CHECKSHEET / "ENGINE" / "_SIAP_UPLOAD_GSHEET",
    CHECKSHEET / "POWERTRAIN" / "_SIAP_UPLOAD_GSHEET",
]


def probe(path: Path) -> dict:
    """Ringkas isi xlsx tanpa membukanya sebagai workbook."""
    info = {
        "bytes": path.stat().st_size,
        "media_files": 0,
        "media_bytes": 0,
        "drawings": 0,
        "charts": 0,
        "vml": 0,
        "comments": 0,
        "sheets": 0,
        "merged_cells": 0,
        "data_validations": 0,
        "conditional_formats": 0,
        "page_setup": 0,
        "error": None,
    }

    try:
        with zipfile.ZipFile(path) as z:
            for item in z.infolist():
                name = item.filename
                if name.startswith("xl/media/"):
                    info["media_files"] += 1
                    info["media_bytes"] += item.file_size
                elif name.startswith("xl/drawings/drawing"):
                    info["drawings"] += 1
                elif name.startswith("xl/charts/"):
                    info["charts"] += 1
                elif name.endswith(".vml"):
                    info["vml"] += 1
                elif name.startswith("xl/comments"):
                    info["comments"] += 1
                elif re.match(r"xl/worksheets/sheet\d+\.xml$", name):
                    info["sheets"] += 1

            for item in z.infolist():
                if not re.match(r"xl/worksheets/sheet\d+\.xml$", item.filename):
                    continue
                xml = z.read(item.filename).decode("utf-8", errors="ignore")
                info["merged_cells"] += xml.count("<mergeCell ")
                info["data_validations"] += xml.count("<dataValidation ")
                info["conditional_formats"] += xml.count("<conditionalFormatting")
                info["page_setup"] += xml.count("<pageSetup")
    except Exception as e:  # noqa: BLE001
        info["error"] = str(e)

    return info


# File yang di-rename setelah backup asli dibuat.
RENAMES = {
    "POWERTRAIN/_SIAP_UPLOAD_GSHEET/Control Valve/D155-6/DISASSEMBLY Control Valve D155-6.xlsx":
        "DISASSEMBLY Control Valve D155.xlsx",
}


def find_backup(rel: Path, backup_dir: Path) -> Path | None:
    candidate = backup_dir / "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY" / rel

    rel_str = str(rel).replace("\\", "/")
    if rel_str in RENAMES:
        candidate = candidate.parent / RENAMES[rel_str]

    return candidate if candidate.exists() else None


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--json", dest="json_out")
    ap.add_argument(
        "--backup",
        default="20260726_182618",
        help="nama folder backup pembanding (default: backup pertama = file asli)",
    )
    args = ap.parse_args()

    backup_dir = BACKUP_ROOT / args.backup
    if not backup_dir.is_dir():
        raise SystemExit(f"Backup tidak ditemukan: {backup_dir}")

    rows = []

    for live_dir in LIVE_DIRS:
        if not live_dir.is_dir():
            continue
        for path in sorted(live_dir.rglob("*.xlsx")):
            if path.name.startswith("~$"):
                continue

            rel = path.relative_to(CHECKSHEET)
            backup = find_backup(rel, backup_dir)

            cur = probe(path)
            old = probe(backup) if backup else None

            rows.append(
                {
                    "file": str(rel).replace("\\", "/"),
                    "has_backup": backup is not None,
                    "current": cur,
                    "backup": old,
                }
            )

    # Laporan
    print("=" * 100)
    print(f"INSPEKSI KERUSAKAN FORMAT XLSX  (pembanding: _SIAP_BACKUP/{args.backup})")
    print("=" * 100)

    damaged = []
    clean = []
    no_backup = []

    for r in rows:
        cur, old = r["current"], r["backup"]
        if old is None:
            no_backup.append(r["file"])
            continue

        problems = []
        if old["media_files"] and cur["media_files"] < old["media_files"]:
            problems.append(
                f"gambar hilang {old['media_files'] - cur['media_files']}/{old['media_files']}"
            )
        if old["drawings"] and cur["drawings"] < old["drawings"]:
            problems.append(f"drawing hilang {old['drawings'] - cur['drawings']}/{old['drawings']}")
        if old["charts"] and cur["charts"] < old["charts"]:
            problems.append(f"chart hilang {old['charts'] - cur['charts']}/{old['charts']}")
        if old["comments"] and cur["comments"] < old["comments"]:
            problems.append(f"komentar hilang {old['comments'] - cur['comments']}")
        if old["conditional_formats"] and cur["conditional_formats"] < old["conditional_formats"]:
            problems.append(
                f"cond.format hilang {old['conditional_formats'] - cur['conditional_formats']}"
            )
        if old["merged_cells"] and cur["merged_cells"] < old["merged_cells"]:
            problems.append(f"merge hilang {old['merged_cells'] - cur['merged_cells']}")
        if old["page_setup"] and cur["page_setup"] < old["page_setup"]:
            problems.append(f"pageSetup hilang {old['page_setup'] - cur['page_setup']}")
        if cur["data_validations"] > max(old["data_validations"] * 3, old["data_validations"] + 50):
            problems.append(
                f"dataValidation membengkak {old['data_validations']} -> {cur['data_validations']}"
            )
        if cur["sheets"] != old["sheets"]:
            problems.append(f"jumlah sheet {old['sheets']} -> {cur['sheets']}")

        entry = {"file": r["file"], "problems": problems, "current": cur, "backup": old}
        (damaged if problems else clean).append(entry)

    print(f"\nRUSAK: {len(damaged)} file")
    for e in damaged:
        cur, old = e["current"], e["backup"]
        print(f"\n  {e['file']}")
        print(
            f"    ukuran {old['bytes']:>10,} -> {cur['bytes']:>10,} bytes   "
            f"media {old['media_files']:>3} -> {cur['media_files']:<3} "
            f"({old['media_bytes']:,} -> {cur['media_bytes']:,} bytes)"
        )
        for p in e["problems"]:
            print(f"    ! {p}")

    print(f"\n\nUTUH: {len(clean)} file")
    for e in clean:
        print(f"  {e['file']}")

    if no_backup:
        print(f"\n\nTANPA BACKUP PEMBANDING: {len(no_backup)} file")
        for f in no_backup:
            print(f"  {f}")

    print("\n" + "-" * 100)
    print(f"Total: {len(rows)} | rusak {len(damaged)} | utuh {len(clean)} | tanpa backup {len(no_backup)}")

    if args.json_out:
        Path(args.json_out).write_text(
            json.dumps({"rows": rows, "damaged": [d["file"] for d in damaged]}, indent=2, ensure_ascii=False),
            encoding="utf-8",
        )
        print(f"JSON: {args.json_out}")


if __name__ == "__main__":
    main()
