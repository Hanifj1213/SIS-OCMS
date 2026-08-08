#!/usr/bin/env python3
"""
Kembalikan file _SIAP_UPLOAD_GSHEET ke kondisi asli dari backup.

Dipakai setelah ketahuan openpyxl merusak format (gambar hilang, drawing
hilang, dataValidation membengkak) saat menulis ulang workbook.

Kondisi sekarang disalin dulu ke _SIAP_BACKUP/{ts}_before_restore/ sebelum
ditimpa, jadi langkah ini bisa dibatalkan.

Jalankan:
  python tools/restore_siap_from_backup.py --dry-run
  python tools/restore_siap_from_backup.py
"""
from __future__ import annotations

import argparse
import filecmp
import shutil
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CHECKSHEET = ROOT / "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY"
BACKUP_ROOT = CHECKSHEET / "_SIAP_BACKUP"

LIVE_DIRS = [
    CHECKSHEET / "ENGINE" / "_SIAP_UPLOAD_GSHEET",
    CHECKSHEET / "POWERTRAIN" / "_SIAP_UPLOAD_GSHEET",
]

# File yang sempat di-rename setelah backup dibuat.
# {path relatif sekarang: nama file di backup}
RENAMES = {
    "POWERTRAIN/_SIAP_UPLOAD_GSHEET/Control Valve/D155-6/DISASSEMBLY Control Valve D155-6.xlsx":
        "DISASSEMBLY Control Valve D155.xlsx",
}


def backup_path_for(rel: Path, backup_dir: Path) -> Path:
    rel_str = str(rel).replace("\\", "/")
    base = backup_dir / "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY" / rel

    if rel_str in RENAMES:
        return base.parent / RENAMES[rel_str]

    return base


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--backup", default="20260726_182618", help="folder backup sumber (file asli)")
    args = ap.parse_args()

    backup_dir = BACKUP_ROOT / args.backup
    if not backup_dir.is_dir():
        raise SystemExit(f"Backup tidak ditemukan: {backup_dir}")

    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    safety_root = BACKUP_ROOT / f"{ts}_before_restore"

    restored = identical = missing = 0

    for live_dir in LIVE_DIRS:
        if not live_dir.is_dir():
            continue

        for path in sorted(live_dir.rglob("*.xlsx")):
            if path.name.startswith("~$"):
                continue

            rel = path.relative_to(CHECKSHEET)
            src = backup_path_for(rel, backup_dir)

            if not src.exists():
                print(f"  ?? TANPA BACKUP  {rel}")
                missing += 1
                continue

            if filecmp.cmp(src, path, shallow=False):
                identical += 1
                continue

            size_before = path.stat().st_size
            size_after = src.stat().st_size
            print(f"  restore  {str(rel)[:78]:80s} {size_before:>10,} -> {size_after:>10,}")

            if not args.dry_run:
                safety = safety_root / rel
                safety.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(path, safety)
                shutil.copy2(src, path)

            restored += 1

    print("\n" + "-" * 96)
    print(
        f"{'DRY RUN — ' if args.dry_run else ''}"
        f"dipulihkan {restored} | sudah sama {identical} | tanpa backup {missing}"
    )
    if not args.dry_run and restored:
        print(f"Kondisi sebelum restore disimpan di: {safety_root.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
