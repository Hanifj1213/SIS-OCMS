    <style>
        .stage-review-link {
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }
        .stage-review-link:hover {
            transform: translateY(-2px);
            filter: brightness(1.15);
        }
        .stage-node.reviewing {
            outline: 2px solid var(--accent-gold);
            outline-offset: 2px;
        }

        /* === Waktu 3 Dimensi (Calendar / Work / Man Hour) === */
        .time3d-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }
        .time3d-tile {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 10px 14px;
            background: rgba(var(--ink), 0.02);
        }
        .time3d-tile::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: var(--tile-accent, var(--accent-cyan));
            opacity: 0.7;
        }
        .time3d-tile[data-metric="calendar"] { --tile-accent: var(--accent-cyan); }
        .time3d-tile[data-metric="work"]     { --tile-accent: var(--accent-gold); }
        .time3d-tile[data-metric="man"]      { --tile-accent: var(--accent-green); }
        .t3-label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .t3-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
            font-weight: 700;
            margin-top: 4px;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }
        .time3d-grid[data-running="1"] .t3-value { color: var(--tile-accent, var(--accent-cyan)); }
        .t3-sub {
            font-size: 0.62rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .t3-pulse {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--accent-cyan);
            animation: t3blink 1.2s ease-in-out infinite;
            display: none;
        }
        .time3d-grid[data-running="1"] .t3-pulse { display: inline-block; }
        @keyframes t3blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.2; } }

        .crew-panel {
            margin-top: 12px;
            border: 1px dashed var(--glass-border-light);
            border-radius: 12px;
            padding: 12px 14px;
            background: rgba(var(--ink), 0.015);
        }
        .crew-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(var(--ink), 0.05);
            border: 1px solid var(--glass-border-light);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .crew-chip:hover {
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.5);
            background: rgba(248, 113, 113, 0.08);
        }

        /* Baris info komponen: label kiri, nilai sejajar di kolom kanan */
        .dc-info-row {
            align-items: baseline;
            gap: 12px;
        }
        .dc-info-row .dc-label {
            flex-shrink: 0;
            line-height: 1.35;
        }
        .dc-info-row .dc-value {
            flex: 1;
            min-width: 0;
            text-align: left;
        }
        .dc-info-row .dc-value .badge {
            max-width: 100%;
        }
        .dc-info-row-stacked .dc-value {
            text-align: left;
        }

        /* ===== Tampilan HP ===== */
        @media (max-width: 768px) {
            /* Damage Core: info + QR ditumpuk, kolom kiri/kanan jadi satu */
            .dc-layout { grid-template-columns: 1fr !important; }
            .dc-cols { grid-template-columns: 1fr !important; }
            .dc-col-l { border-right: none !important; }
            .dc-col-l .dc-info-row { padding-right: 0 !important; }
            .dc-col-r { padding-left: 0 !important; padding-top: 8px; }

            /* Info komponen: label kiri, isian sejajar kolom (bukan rata kanan) */
            .dc-info-row {
                flex-direction: row !important;
                align-items: baseline !important;
                gap: 10px;
                padding: 7px 0 !important;
            }
            .dc-info-row .dc-label {
                width: 118px !important;
                max-width: 118px;
                font-size: 0.72rem !important;
            }
            .dc-info-row .dc-value {
                flex: 1;
                font-size: 0.82rem;
                text-align: left;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .dc-info-row-stacked {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 6px;
            }
            .dc-info-row-stacked .dc-label {
                width: auto !important;
                max-width: none;
            }
            .dc-info-row-stacked .dc-value {
                width: 100%;
            }
            .badge-wrap {
                white-space: normal !important;
                text-align: left;
                line-height: 1.3;
                display: inline-block;
                max-width: 100%;
            }

            /* Riwayat pengerjaan: header & badge tidak kepotong kanan */
            .timeline-entry { gap: 12px !important; overflow: hidden; }
            .timeline-entry-body { min-width: 0; flex: 1; overflow: hidden; }
            .timeline-entry-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px;
            }
            .timeline-stage-title {
                word-break: break-word;
                overflow-wrap: anywhere;
                padding-right: 0 !important;
                line-height: 1.35;
            }
            .timeline-entry-badge { align-self: flex-start; flex-shrink: 0; }

            /* Kartu waktu 3 dimensi ditumpuk vertikal */
            .time3d-grid { grid-template-columns: 1fr; gap: 8px; }
            .t3-value { font-size: 0.95rem; }

            /* Panel crew: form tambah nama turun ke baris sendiri, full width */
            .crew-panel { overflow: hidden; }
            .crew-add-form {
                margin-left: 0 !important;
                flex-basis: 100%;
                width: 100%;
                max-width: 100%;
            }
            .crew-add-form input[type="text"] {
                width: auto !important;
                flex: 1;
                min-width: 0;
            }
            .crew-add-form button { flex-shrink: 0; white-space: nowrap; }

            /* Tombol aksi (Kembali / Approve / Ajukan) boleh turun baris */
            .action-bar { flex-wrap: wrap; gap: 12px; }
            .action-bar > div { flex-wrap: wrap; }

            /* Embed GSheet & PDF sedikit lebih pendek supaya tidak memakan layar */
            .gsheet-shell { height: 72vh !important; }
            .fr-pdf-embed { height: 60vh !important; }
        }

        @media (max-width: 480px) {
            .time3d-tile { padding: 9px 12px; }
            .crew-panel { padding: 10px 12px; }
        }
    </style>
