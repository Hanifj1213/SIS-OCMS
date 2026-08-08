"""Bandingkan proporsi PDF FR hasil aplikasi dengan form asli.

Dipakai saat menyetel tata letak agar tidak menebak: semua angka dibaca
langsung dari garis tabel dan posisi teks di kedua PDF.

Jalankan: python tools/check_fr_layout.py
"""

import sys
import fitz

ASLI = r'FABRIKASI/FR 2026/1. SALVAGE TF HD785-7 DT090-0146B 2700046897 ADMO WARRANTY LOKAL.pdf'
KITA = 'fr-preview.pdf'

MARKS = [
    'Sent To', 'Requirement Date', 'Work Order For',
    'DETAIL INSTRUCTION', 'PART MATERIAL', 'TOTAL PART', 'Green/Hijau',
]


def geometry(path):
    page = fitz.open(path)[0]
    horizontals, verticals = set(), set()

    for drawing in page.get_drawings():
        for item in drawing['items']:
            if item[0] == 'l':
                a, b = item[1], item[2]
                if abs(a.y - b.y) < 1.2:
                    horizontals.add((round(a.y), round(min(a.x, b.x)), round(max(a.x, b.x))))
                elif abs(a.x - b.x) < 1.2:
                    verticals.add(round(a.x))
            elif item[0] == 're':
                r = item[1]
                horizontals.add((round(r.y0), round(r.x0), round(r.x1)))
                horizontals.add((round(r.y1), round(r.x0), round(r.x1)))
                verticals.update({round(r.x0), round(r.x1)})

    wide = sorted({y for y, x0, x1 in horizontals if x1 - x0 > 400})
    return page, wide, sorted(verticals)


def report(tag, path):
    page, wide, xs = geometry(path)
    top, bottom = (wide[0], wide[-1]) if wide else (0, 0)
    left, right = xs[2], xs[-2]

    print(f'--- {tag} ---')
    print(f'  area isi : {right - left}pt lebar x {bottom - top}pt tinggi')

    found = {}
    for mark in MARKS:
        hits = page.search_for(mark)
        found[mark] = hits[0].y0 if hits else None
        label = f'{found[mark]:.0f}' if hits else 'TIDAK ADA'
        print(f'  {mark:20s} y={label}')

    return found, bottom - top


def main():
    asli, h_asli = report('ASLI', ASLI)
    print()
    kita, h_kita = report('APLIKASI', KITA)

    print()
    print('--- TINGGI BLOK (pt) ---')
    blocks = [
        ('identitas+approval', 'Sent To', 'DETAIL INSTRUCTION'),
        ('detail+material', 'DETAIL INSTRUCTION', 'TOTAL PART'),
    ]
    for name, start, end in blocks:
        a = asli.get(start), asli.get(end)
        k = kita.get(start), kita.get(end)
        if all(a) and all(k):
            da, dk = a[1] - a[0], k[1] - k[0]
            print(f'  {name:20s} asli={da:5.0f}  aplikasi={dk:5.0f}  selisih={dk - da:+5.0f}')

    print(f'  {"total tinggi":20s} asli={h_asli:5.0f}  aplikasi={h_kita:5.0f}  selisih={h_kita - h_asli:+5.0f}')
    return 0


if __name__ == '__main__':
    sys.exit(main())
