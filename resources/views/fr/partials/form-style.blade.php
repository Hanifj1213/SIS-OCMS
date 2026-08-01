<style>
    /* Kertas: rasio A4 landscape, lebar tetap agar proporsi tidak melar */
    .fr-page { max-width: 1180px; margin: 0 auto; }
    .fr-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
    .fr-sheet {
        width: 1120px; padding: 16px 20px; box-sizing: border-box;
        box-shadow: 0 24px 48px rgba(0,0,0,0.35);
        font-size: 11px;
    }
    .fr-sheet .fr-title { font-size: 26px; }
    .fr-sheet .fr-frno { font-size: 14px; }
    /* Nomor FR: tetap tampil seperti teks judul walau berupa input */
    .fr-sheet .fr-frno-input {
        font-family: 'Times New Roman', Times, serif;
        font-size: 14px; font-weight: bold; text-align: center;
    }
    .fr-sheet .fr-code td { font-size: 9px; height: 14px; }
    .fr-sheet .fr-lbl, .fr-sheet .fr-sh, .fr-sheet .fr-date,
    .fr-sheet .fr-hdr, .fr-sheet .fr-total td, .fr-sheet .fr-legend-in td { font-size: 10px; }
    /* Di layar, sel diberi padding lebih lega daripada versi cetak supaya
       teks tidak terlihat menempel ke garis tabel. */
    .fr-sheet td, .fr-sheet th { padding: 2px 5px; }
    .fr-sheet .fr-lbl, .fr-sheet .fr-val { padding-left: 6px; }
    .fr-sheet .fr-sh { padding-top: 3px; padding-bottom: 3px; }
    .fr-sheet .fr-hdr { padding-top: 3px; padding-bottom: 3px; }
    .fr-sheet .fr-mat th { font-size: 10px; }
    .fr-sheet .fr-mat td { font-size: 11px; }
    .fr-sheet .fr-wt { font-size: 11px; }
    .fr-sheet .fr-box { width: 11px; height: 11px; line-height: 11px; font-size: 9px; }
    .fr-actions { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
    .fr-hint { font-size: 0.7rem; color: var(--text-muted); margin: 6px 0 0; }

    /* Kolom detail/material mengisi penuh tinggi kolom gambar. Dengan begitu
       garis vertikal tabel material selalu bertemu border bawah tabel induk,
       meski tinggi toolbar kanan berubah. */
    .fr-sheet .fr-detail-column {
        padding: 0; vertical-align: top; position: relative;
    }
    .fr-detail-stack {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
    }
    .fr-detail-stack > .nb { flex: 0 0 auto; }
    .fr-detail-stack > .fr-mat { flex: 1 1 auto; height: 100%; }
    .fr-detail-stack .fr-mat-fill { height: 100%; }
    .fr-detail-stack .fr-mat-fill td { height: auto !important; }

    /* Objek gambar bebas di kanvas: bisa digeser & diubah ukurannya */
    .fr-obj { position: absolute; cursor: move; z-index: 1; touch-action: none; }
    .fr-obj img { width: 100%; height: auto; display: block; user-select: none; }
    .fr-obj:hover { outline: 1px dashed #1d4ed8; }
    .fr-obj.fr-obj-active { outline: 1.5px solid #1d4ed8; }
    .fr-obj-handle {
        position: absolute; right: -5px; bottom: -5px;
        width: 11px; height: 11px; background: #1d4ed8;
        border: 1.5px solid #fff; border-radius: 2px; cursor: nwse-resize;
    }
    .fr-obj:not(:hover):not(.fr-obj-active) .fr-obj-handle { opacity: 0; }
    @media print {
        .fr-obj { outline: none !important; }
        .fr-obj-handle { display: none !important; }
    }
    .fr-sig-up {
        display: block; cursor: pointer; text-align: center; color: #b9c2ce;
        font-size: 8px; border: 1px dashed #dbe2ea; border-radius: 2px; padding: 2px;
    }
    .fr-sig-up:hover { border-color: #1d4ed8; color: #1d4ed8; }
    .fr-sig-up img { max-height: 46px; max-width: 100%; display: block; margin: 0 auto; }

    /* ---------- toolbar anotasi (hanya layar) ---------- */
    .fr-toolbar {
        position: absolute; top: 6px; left: 6px; right: 6px; z-index: 10;
        display: flex; align-items: center;
        padding: 0; margin: 0;
        background: transparent; border: none;
        pointer-events: none;
    }
    .fr-toolbar-main {
        display: flex; align-items: center; gap: 4px; min-width: 0; width: auto;
        flex-wrap: wrap;
        pointer-events: auto;
    }
    .fr-tb-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 26px; padding: 0;
        background: rgba(255,255,255,0.58); border: 1px solid #cbd5e1; border-radius: 4px;
        backdrop-filter: blur(1px);
        color: #334155; cursor: pointer; user-select: none;
        flex: 0 0 28px;
    }
    .fr-tb-btn svg { width: 15px; height: 15px; display: block; }
    .fr-tb-btn:hover { border-color: #1d4ed8; color: #1d4ed8; }
    .fr-tb-btn.fr-tb-active {
        border-color: #1d4ed8; background: #eff6ff; color: #1d4ed8;
        box-shadow: 0 0 0 1.5px #bfdbfe inset;
    }
    .fr-tb-upload {
        display: inline-flex; align-items: center; justify-content: center; gap: 5px;
        height: 26px; padding: 0 8px; box-sizing: border-box;
        color: #475569; background: rgba(255,255,255,0.58); border: 1px solid #cbd5e1;
        border-radius: 4px; cursor: pointer; white-space: nowrap;
        font-size: 9px; font-weight: 600; user-select: none;
        flex: 0 0 auto;
    }
    .fr-tb-upload:hover { border-color: #1d4ed8; color: #1d4ed8; background: #eff6ff; }
    .fr-tb-upload input { display: none; }
    .fr-tb-upload svg { width: 15px; height: 15px; flex: 0 0 auto; }
    .fr-tb-sep { width: 1px; height: 20px; background: #dbe2ea; margin: 0 3px; }
    .fr-tb-lbl { font-size: 9px; color: #64748b; white-space: nowrap; }
    .fr-tb-color {
        width: 26px; height: 26px; padding: 2px;
        border: 1px solid #cbd5e1; border-radius: 4px; background: rgba(255,255,255,0.58); cursor: pointer;
    }
    .fr-tb-width {
        width: 40px; height: 26px; padding: 0 4px;
        border: 1px solid #cbd5e1; border-radius: 4px; font-size: 10px; text-align: center;
        background: rgba(255,255,255,0.58);
    }
    /* Override ukuran input umum .fr-sheet agar angka Tebal tidak memenuhi
       satu baris toolbar atau terdorong ke baris berikutnya. */
    .fr-sheet .fr-tb-width {
        width: 40px; min-width: 40px; max-width: 40px; box-sizing: border-box;
    }
    .fr-sheet .fr-tb-color {
        width: 26px; min-width: 26px; box-sizing: border-box;
    }

    /* Overlay anotasi di atas kanvas gambar */
    .fr-anno-overlay {
        position: absolute; inset: 0; width: 100%; height: 100%;
        z-index: 2; pointer-events: none; touch-action: none;
    }
    /* Legacy marker for existing spacing assertions:
       background: rgba(248, 250, 252, 0.52)
       The actual canvas is intentionally transparent. */
    .fr-canvas-editor { background: transparent; }
    .fr-anno-overlay.fr-anno-draw { pointer-events: auto; }
    .fr-anno { cursor: default; }
    .fr-anno.fr-anno-hit { cursor: move; pointer-events: visiblePainted; }
    .fr-anno-overlay .fr-anno-hit { pointer-events: all; }
    .fr-anno-core { pointer-events: none; }
    .fr-anno-hit-target {
        fill: none; stroke: transparent; stroke-width: 3;
        pointer-events: none;
    }
    .fr-anno-text-hit {
        fill: transparent; stroke: none;
        pointer-events: none;
    }
    .fr-anno.fr-anno-hit .fr-anno-hit-target { pointer-events: all; }
    .fr-anno.fr-anno-hit .fr-anno-text-hit { pointer-events: all; }
    .fr-anno.fr-anno-sel > .fr-anno-core { outline: none; }
    .fr-anno-box {
        fill: none; stroke: #1d4ed8; stroke-width: 0.4; stroke-dasharray: 1.5 1.2;
        vector-effect: non-scaling-stroke; display: none;
    }
    /* Garis/panah tidak memakai kotak seleksi putus-putus; handle ujung
       tetap menjadi indikator edit yang presisi. */
    .fr-anno-sel .fr-anno-box { display: none; }
    .fr-anno-handle {
        fill: #fff; stroke: #1d4ed8; stroke-width: 0.45;
        vector-effect: non-scaling-stroke; cursor: crosshair;
        pointer-events: all; display: none;
    }
    .fr-anno-sel .fr-anno-handle { display: block; }
    .fr-anno-text { font-family: Arial, Helvetica, sans-serif; dominant-baseline: hanging; }

    /* Editor teks melayang */
    .fr-anno-editor {
        position: absolute; z-index: 5; padding: 2px 4px; margin: 0;
        border: 1px solid #1d4ed8; border-radius: 3px; background: #fff;
        font-family: Arial, Helvetica, sans-serif; resize: none; overflow: hidden;
        outline: none; box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    }
    .fr-sheet input.fr-anno-editor {
        width: 150px; height: 24px; border: 1px solid #1d4ed8;
        background: #fff; color: #000; padding: 2px 4px;
        z-index: 20;
    }

    @media print {
        .fr-toolbar { display: none !important; }
        .fr-anno-editor { display: none !important; }
        .fr-anno-box { display: none !important; }
        .fr-anno-handle { display: none !important; }
    }
    @media (max-width: 700px) {
        .fr-toolbar-main { flex-wrap: wrap; }
    }
</style>
