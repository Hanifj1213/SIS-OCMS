<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checksheet — {{ $comp->serial_number }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --bg-primary: #0B2B26;
            --bg-secondary: #091528;
            --accent-gold: #D4AF37;
            --accent-gold-dim: rgba(212, 175, 55, 0.15);
            --accent-cyan: #48CAE4;
            --accent-cyan-dim: rgba(72, 202, 228, 0.12);
            --accent-green: #34D399;
            --accent-green-dim: rgba(52, 211, 153, 0.12);
            --accent-red: #F87171;
            --accent-red-dim: rgba(248, 113, 113, 0.12);
            --glass-border: rgba(255, 255, 255, 0.06);
            --glass-border-light: rgba(255, 255, 255, 0.10);
            --text-primary: rgba(255, 255, 255, 0.92);
            --text-secondary: rgba(255, 255, 255, 0.55);
            --text-muted: rgba(255, 255, 255, 0.25);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(170deg, var(--bg-primary) 0%, var(--bg-secondary) 40%, #0d1f3c 100%);
            color: var(--text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* === HEADER === */
        .cs-header {
            padding: 16px 24px;
            background: rgba(11, 43, 38, 0.6);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            z-index: 10;
        }

        .cs-header-back {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .cs-header-back:hover {
            color: var(--text-primary);
        }

        .cs-header-title {
            text-align: center;
        }

        .cs-header-title h2 {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent-gold);
        }

        .cs-header-title span {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        .cs-header-counter {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-cyan);
        }

        /* === PROGRESS BAR === */
        .cs-progress {
            padding: 0 24px;
            flex-shrink: 0;
            margin-top: 12px;
        }

        .cs-progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 3px;
            overflow: hidden;
        }

        .cs-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-green));
            border-radius: 3px;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .cs-progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* === TOGGLE HEADER === */
        .cs-header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .cs-toggle-group {
            display: flex;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            padding: 4px;
            border: 1px solid var(--glass-border);
        }

        .cs-toggle-btn {
            padding: 6px 12px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .cs-toggle-btn.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-cyan);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* === SLIDE REF IMAGE === */
        .cs-slide-ref {
            margin-bottom: 12px;
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cs-slide-ref-thumb {
            width: 420px;
            height: auto;
            max-height: 300px;
            object-fit: contain;
            border-radius: 10px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            cursor: zoom-in;
            transition: all 0.25s;
            opacity: 0.85;
            background: rgba(255, 255, 255, 0.02);
        }

        .cs-slide-ref-thumb:hover {
            opacity: 1;
            border-color: var(--accent-gold);
            transform: scale(1.05);
            box-shadow: 0 6px 24px rgba(212, 175, 55, 0.25);
        }

        .cs-slide-ref-label {
            font-size: 0.55rem;
            font-weight: 600;
            color: var(--accent-gold);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 4px;
            text-align: center;
        }

        /* Image Lightbox */
        .cs-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(12px);
            z-index: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            cursor: zoom-out;
        }

        .cs-lightbox img {
            max-width: 95%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 16px 64px rgba(0, 0, 0, 0.5);
        }

        .cs-lightbox-close {
            position: fixed;
            top: 20px;
            right: 24px;
            color: white;
            font-size: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 301;
        }

        .cs-lightbox-label {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--accent-gold);
            font-size: 0.85rem;
            font-weight: 700;
            background: rgba(0, 0, 0, 0.6);
            padding: 8px 20px;
            border-radius: 8px;
        }

        /* === SLIDE CONTAINER === */
        .cs-slide-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
            position: relative;
        }

        .cs-slide {
            width: 100%;
            max-width: 520px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .cs-slide.slide-enter-right {
            transform: translateX(100px);
            opacity: 0;
        }

        .cs-slide.slide-enter-left {
            transform: translateX(-100px);
            opacity: 0;
        }

        .cs-slide.slide-active {
            transform: translateX(0);
            opacity: 1;
        }

        /* === DAFTAR CONTAINER === */
        .cs-daftar-area {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            position: relative;
        }

        .cs-daftar-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .cs-list-group {
            margin-bottom: 24px;
        }

        .cs-list-group-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--accent-gold);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--glass-border);
        }

        .cs-list-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .cs-list-item-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cs-list-item-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-cyan);
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--accent-cyan-dim);
            flex-shrink: 0;
        }

        .cs-list-item-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .cs-list-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .cs-list-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.05em;
        }

        .cs-list-badge.good {
            background: var(--accent-green-dim);
            color: var(--accent-green);
            border: 1px solid rgba(52, 211, 153, 0.3);
        }

        .cs-list-badge.bad {
            background: var(--accent-red-dim);
            color: var(--accent-red);
            border: 1px solid rgba(248, 113, 113, 0.3);
        }

        .cs-list-badge.none {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-muted);
            border: 1px solid var(--glass-border);
        }

        .cs-list-badge.unanswered {
            background: transparent;
            color: var(--text-muted);
            border: 1px dashed var(--glass-border);
            font-style: italic;
        }

        .cs-list-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .cs-list-icon-btn.delete:hover {
            background: var(--accent-red-dim);
            border-color: rgba(248, 113, 113, 0.3);
            color: var(--accent-red);
        }

        .cs-list-icon-btn.add:hover {
            background: var(--accent-green-dim);
            border-color: rgba(52, 211, 153, 0.3);
            color: var(--accent-green);
        }

        .cs-daftar-filter {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .cs-filter-btn {
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .cs-filter-btn.active {
            background: rgba(255, 255, 255, 0.08);
            color: var(--accent-cyan);
            border-color: rgba(72, 202, 228, 0.3);
        }

        .cs-group-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--accent-gold);
            margin-bottom: 12px;
        }

        .cs-item-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 3rem;
            font-weight: 900;
            color: rgba(255, 255, 255, 0.06);
            margin-bottom: 8px;
            line-height: 1;
        }

        .cs-item-label {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .cs-item-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        /* === ANSWER BUTTONS === */
        .cs-answers {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cs-answer-btn {
            flex: 1;
            min-width: 100px;
            max-width: 160px;
            padding: 20px 16px;
            border-radius: 16px;
            border: 2px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.03);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            -webkit-tap-highlight-color: transparent;
        }

        .cs-answer-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
        }

        .cs-answer-btn:active {
            transform: scale(0.96);
        }

        .cs-answer-btn .cs-answer-icon {
            font-size: 1.8rem;
        }

        .cs-answer-btn .cs-answer-text {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .cs-answer-btn .cs-answer-key {
            font-size: 0.6rem;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-muted);
            padding: 2px 8px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 4px;
        }

        /* Answer states */
        .cs-answer-btn.good {
            border-color: rgba(52, 211, 153, 0.3);
        }

        .cs-answer-btn.good:hover,
        .cs-answer-btn.good.selected {
            background: var(--accent-green-dim);
            border-color: var(--accent-green);
            color: var(--accent-green);
        }

        .cs-answer-btn.bad {
            border-color: rgba(248, 113, 113, 0.3);
        }

        .cs-answer-btn.bad:hover,
        .cs-answer-btn.bad.selected {
            background: var(--accent-red-dim);
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .cs-answer-btn.none {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .cs-answer-btn.none:hover,
        .cs-answer-btn.none.selected {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.2);
            color: var(--text-secondary);
        }

        /* === NAVIGATION === */
        .cs-nav {
            padding: 16px 24px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            gap: 12px;
        }

        .cs-nav-btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cs-nav-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-primary);
        }

        .cs-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .cs-nav-btn.finish {
            background: linear-gradient(135deg, var(--accent-gold), #EAA112);
            color: #0B2B26;
            border: none;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(212, 175, 55, 0.2);
        }

        .cs-nav-btn.finish:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
        }

        .cs-add-btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px dashed rgba(212, 175, 55, 0.3);
            background: transparent;
            color: var(--accent-gold);
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cs-add-btn:hover {
            background: var(--accent-gold-dim);
            border-style: solid;
        }

        /* === COMPLETION SCREEN === */
        .cs-complete {
            text-align: center;
        }

        .cs-complete-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }

        .cs-complete h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-green);
            margin-bottom: 8px;
        }

        .cs-complete p {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }

        .cs-complete-stats {
            display: flex;
            gap: 24px;
            justify-content: center;
            margin-bottom: 32px;
        }

        .cs-stat {
            text-align: center;
        }

        .cs-stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2rem;
            font-weight: 900;
        }

        .cs-stat-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
        }

        /* === MODAL === */
        .cs-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 24px;
        }

        .cs-modal {
            background: linear-gradient(170deg, #0f3d36, var(--bg-secondary));
            border: 1px solid var(--glass-border-light);
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 420px;
        }

        .cs-modal h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--accent-gold);
        }

        .cs-modal input,
        .cs-modal select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            outline: none;
            margin-bottom: 16px;
        }

        .cs-modal input:focus,
        .cs-modal select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .cs-modal select option {
            background: var(--bg-secondary);
            color: white;
        }

        .cs-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        /* === RESPONSIVE === */
        @media (max-width: 480px) {
            .cs-item-label {
                font-size: 1.1rem;
            }

            .cs-item-number {
                font-size: 2.2rem;
            }

            .cs-answers {
                flex-direction: column;
                align-items: center;
            }

            .cs-answer-btn {
                max-width: 100%;
                width: 100%;
                flex-direction: row;
                padding: 16px 20px;
            }

            .cs-answer-btn .cs-answer-icon {
                font-size: 1.4rem;
            }

            .cs-answer-btn .cs-answer-key {
                display: none;
            }
        }

        /* Toast */
        .cs-toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: rgba(52, 211, 153, 0.15);
            border: 1px solid rgba(52, 211, 153, 0.3);
            color: var(--accent-green);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0;
            transition: all 0.3s;
            z-index: 200;
            pointer-events: none;
        }

        .cs-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="cs-header">
        <a href="{{ route('components.show', $comp->comp_id) }}" class="cs-header-back">
            ← Kembali
        </a>
        <div class="cs-header-title">
            <h2>{{ $comp->major_category }} Checksheet</h2>
            <span>{{ $stageName }} — {{ $comp->serial_number }}</span>
        </div>
        <div class="cs-header-right">
            <div class="cs-toggle-group">
                <button class="cs-toggle-btn active" id="btnSlideView" onclick="toggleMode('slide')">🔴 Slide</button>
                <button class="cs-toggle-btn" id="btnDaftarView" onclick="toggleMode('daftar')">📋 Daftar</button>
            </div>
            <div class="cs-header-counter" id="counter">
                {{ count($checksheet->answers ?? []) }}/{{ count($checksheet->items) }}
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="cs-progress">
        <div class="cs-progress-bar">
            <div class="cs-progress-fill" id="progressFill" style="width: {{ $checksheet->progress }}%"></div>
        </div>
        <div class="cs-progress-text">
            <span id="progressGroup">—</span>
            <span id="progressPercent">{{ $checksheet->progress }}%</span>
        </div>
    </div>

    <!-- Slide Area -->
    <div class="cs-slide-area" id="slideModeContainer">
        <div class="cs-slide slide-active" id="slideContent">
            <!-- Filled by JS -->
        </div>
    </div>

    <!-- Daftar Area -->
    <div class="cs-daftar-area" id="daftarModeContainer" style="display: none;">
        <div class="cs-daftar-container" id="daftarContent">
            <!-- Filled by JS -->
        </div>
    </div>

    <!-- Image Lightbox -->
    <div class="cs-lightbox" id="lightbox" style="display: none;" onclick="closeLightbox()">
        <button class="cs-lightbox-close" onclick="closeLightbox()">×</button>
        <img id="lightboxImg" src="" alt="">
        <div class="cs-lightbox-label" id="lightboxLabel"></div>
    </div>

    <!-- Navigation (Slide only) -->
    <div class="cs-nav" id="slideNavContainer">
        <button class="cs-nav-btn" id="btnPrev" onclick="navigate(-1)" disabled>← Prev</button>
        <button class="cs-nav-btn" id="btnNext" onclick="navigate(1)">Next →</button>
    </div>

    <!-- Add Item Modal -->
    <div class="cs-modal-overlay" id="addModal" style="display: none;">
        <div class="cs-modal">
            <h3>+ Tambah Item Checksheet</h3>
            <label
                style="font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">Nama
                Item</label>
            <input type="text" id="newItemLabel" placeholder="Contoh: Bracket Custom XYZ">
            <label
                style="font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">Grup</label>
            <select id="newItemGroup">
                <option value="Custom Items">Custom Items</option>
                <option value="Right Side View">Right Side View</option>
                <option value="Left Side View">Left Side View</option>
                <option value="Rear Side View">Rear Side View</option>
            </select>
            <div class="cs-modal-actions">
                <button class="cs-nav-btn" onclick="closeAddModal()">Batal</button>
                <button class="cs-nav-btn finish" onclick="submitAddItem()">Tambahkan</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="cs-toast" id="toast">✓ Tersimpan</div>

    <script>
        (function () {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const COMP_ID = {{ $comp->comp_id }};
            const STAGE = {{ $stage }};

            let items = @json($checksheet->items);
            let answers = @json($checksheet->answers ?? (object) []);
            let currentIndex = 0;
            let canInteract = @json(auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin']));
            let currentMode = 'slide';
            let daftarFilter = 'all';

            function getStageTwoReferenceImages(source) {
                if (!source) return [];

                const root = '/images/inspection/d375-6/stage2/';
                const mainline = source.match(/^D375-6 EG MAINLINE\.pdf p\.(\d+)(?:-(\d+))?$/);
                if (mainline) {
                    const firstPage = Number(mainline[1]);
                    const lastPage = Number(mainline[2] || mainline[1]);
                    const images = [];

                    for (let page = firstPage; page <= lastPage; page++) {
                        images.push({
                            src: root + 'mainline-p' + String(page).padStart(2, '0') + '.jpg',
                            label: 'D375-6 EG MAINLINE - halaman ' + page,
                        });
                    }

                    return images;
                }

                if (source === 'D375-6 EG SUBASSY.pdf p.2' || source === 'D375-6 EG SUBASSY.pdf p.5') {
                    const page = source.endsWith('p.2') ? '02' : '05';
                    return [{ src: root + 'subassy-p' + page + '.jpg', label: 'D375-6 EG SUBASSY - halaman ' + Number(page) }];
                }

                if (source === 'piston 170.pdf p.1 / PISTON CHECKSHEET2.pdf p.1') {
                    return [
                        { src: root + 'piston170-p01.jpg', label: 'Piston, Piston Ring, Piston Pin - halaman 1' },
                        { src: root + 'piston-checksheet-p01.jpg', label: 'Piston Measuring Check Sheet - halaman 1' },
                    ];
                }

                if (source === 'piston 170.pdf p.1') {
                    return [{ src: root + 'piston170-p01.jpg', label: 'Piston, Piston Ring, Piston Pin - halaman 1' }];
                }

                if (source === 'PISTON CHECKSHEET2.pdf p.1' || source === 'PISTON CHECKSHEET2.pdf p.2') {
                    const page = source.endsWith('p.1') ? '01' : '02';
                    return [{ src: root + 'piston-checksheet-p' + page + '.jpg', label: 'Piston Measuring Check Sheet - halaman ' + Number(page) }];
                }

                return [];
            }

            // Stage 2 shows the full original SOP page(s); Stage 1 keeps the
            // existing EGI view-image mapping.
            function getRefImage(item) {
                if (!item.group || item.custom) return null;

                const stageTwoImages = getStageTwoReferenceImages(item.source);
                if (stageTwoImages.length > 0) {
                    return { images: stageTwoImages, label: item.group };
                }

                const knownEgis = ['d375-6','hd785-7','d155-6','wa800-3','gd825a-2','hd465-7r','pc1250-8','pc2000-8','hd1500-7'];
                let egi = "{{ strtolower(trim($comp->egi ?? 'd375-6')) }}";
                if (!knownEgis.includes(egi)) egi = 'd375-6';

                const majorCategory = "{{ $comp->major_category }}";
                const slug = majorCategory === 'Engine'
                    ? item.group.toLowerCase().replace(/ /g, '-')
                    : majorCategory.toLowerCase().replace(/\//g, '-').replace(/ /g, '-');

                return {
                    images: [{
                        src: '/images/inspection/' + egi + '/' + slug + '.png',
                        label: majorCategory === 'Engine' ? item.group : majorCategory + ' Reference',
                    }],
                    label: majorCategory === 'Engine' ? item.group : majorCategory + ' Reference',
                };
            }

            window.toggleMode = function (mode) {
                currentMode = mode;
                document.getElementById('btnSlideView').classList.toggle('active', mode === 'slide');
                document.getElementById('btnDaftarView').classList.toggle('active', mode === 'daftar');
                document.getElementById('slideModeContainer').style.display = mode === 'slide' ? 'flex' : 'none';
                document.getElementById('slideNavContainer').style.display = mode === 'slide' ? 'flex' : 'none';
                document.getElementById('daftarModeContainer').style.display = mode === 'daftar' ? 'block' : 'none';
                document.body.style.overflow = mode === 'daftar' ? 'auto' : 'hidden';
                render();
            };

            window.openLightbox = function(src, label) {
                document.getElementById('lightboxImg').src = src;
                document.getElementById('lightboxLabel').textContent = label;
                document.getElementById('lightbox').style.display = 'flex';
            };

            window.closeLightbox = function() {
                document.getElementById('lightbox').style.display = 'none';
            };

            // Find first unanswered
            for (let i = 0; i < items.length; i++) {
                if (!answers[items[i].id]) { currentIndex = i; break; }
            }

            function render() {
                const total = items.length;
                const answered = Object.keys(answers).length;

                const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
                document.getElementById('progressFill').style.width = pct + '%';
                document.getElementById('progressPercent').textContent = pct + '%';
                document.getElementById('counter').textContent = answered + '/' + total;

                if (currentMode === 'daftar') { renderDaftar(); return; }

                const slide = document.getElementById('slideContent');

                // Completion screen
                if (currentIndex >= total) {
                    const goodCount = Object.values(answers).filter(v => v === 'good').length;
                    const badCount = Object.values(answers).filter(v => v === 'bad').length;
                    const noneCount = Object.values(answers).filter(v => v === 'none').length;

                    document.getElementById('progressGroup').textContent = 'Selesai!';
                    slide.innerHTML = `
                    <div class="cs-complete">
                        <div class="cs-complete-icon">🎉</div>
                        <h2>Checksheet Selesai!</h2>
                        <p>Semua ${total} item telah diperiksa</p>
                        <div class="cs-complete-stats">
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--accent-green);">${goodCount}</div>
                                <div class="cs-stat-label">Good ✓</div>
                            </div>
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--accent-red);">${badCount}</div>
                                <div class="cs-stat-label">Bad ✗</div>
                            </div>
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--text-muted);">${noneCount}</div>
                                <div class="cs-stat-label">N/A —</div>
                            </div>
                        </div>
                        <a href="${'{{ route('components.show', $comp->comp_id) }}'}" class="cs-nav-btn finish" style="text-decoration:none; display:inline-block;">← Kembali ke Detail Komponen</a>
                    </div>
                `;
                    document.getElementById('btnNext').style.display = 'none';
                    document.getElementById('btnPrev').disabled = false;
                    return;
                }

                document.getElementById('btnNext').style.display = '';

                const item = items[currentIndex];
                const currentAnswer = answers[item.id] || null;
                document.getElementById('progressGroup').textContent = item.group || '—';

                // Get reference image for this item
                const refImg = getRefImage(item);
                let refHtml = '';
                if (refImg) {
                    const multiplePages = refImg.images.length > 1;
                    const pageWidth = multiplePages ? 'min(30vw, 220px)' : '420px';
                    const pageHeight = multiplePages ? '250px' : '300px';
                    const imageHtml = refImg.images.map(image => `
                        <div style="text-align:center; min-width:0;">
                            <img src="${image.src}" alt="${image.label}" class="cs-slide-ref-thumb" style="width:${pageWidth}; height:${pageHeight}; max-width:100%; object-fit:contain;" onclick="openLightbox('${image.src}', '${image.label}')" title="📷 ${image.label} — klik untuk perbesar" onerror="this.parentElement.style.display='none'">
                            <div class="cs-slide-ref-label">📷 ${image.label}</div>
                        </div>
                    `).join('');
                    refHtml = `<div class="cs-slide-ref">${imageHtml}</div>`;
                }

                slide.innerHTML = `
                ${refHtml}
                <div class="cs-group-label">${item.group || ''}</div>
                <div class="cs-item-number">#${String(currentIndex + 1).padStart(2, '0')}</div>
                <div class="cs-item-label">${item.label}</div>
                ${item.standard ? `<div style="font-size:0.78rem; color:var(--text-secondary); line-height:1.45; max-width:620px; margin:0 auto 8px;">${item.standard}</div>` : ''}
                <div class="cs-item-meta">${item.custom ? '⚡ Custom Item' : 'Item standar SOP'}${item.source ? ' · ' + item.source : ''}</div>
                <div class="cs-answers">
                    <button class="cs-answer-btn good ${currentAnswer === 'good' ? 'selected' : ''}" onclick="answer('good')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">✓</span>
                        <span class="cs-answer-text">Good</span>
                        <span class="cs-answer-key">1</span>
                    </button>
                    <button class="cs-answer-btn bad ${currentAnswer === 'bad' ? 'selected' : ''}" onclick="answer('bad')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">✗</span>
                        <span class="cs-answer-text">Bad</span>
                        <span class="cs-answer-key">2</span>
                    </button>
                    <button class="cs-answer-btn none ${currentAnswer === 'none' ? 'selected' : ''}" onclick="answer('none')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">—</span>
                        <span class="cs-answer-text">N/A</span>
                        <span class="cs-answer-key">3</span>
                    </button>
                </div>
            `;

                // Nav buttons
                document.getElementById('btnPrev').disabled = currentIndex === 0;
            }

            function renderDaftar() {
                const container = document.getElementById('daftarContent');
                const groups = {};
                items.forEach((item, index) => {
                    const grp = item.group || 'Lainnya';
                    if (!groups[grp]) groups[grp] = [];
                    groups[grp].push({ item, index });
                });

                const allGroups = Object.keys(groups);
                let filterHtml = `<div class="cs-daftar-filter">`;
                filterHtml += `<button class="cs-filter-btn ${daftarFilter === 'all' ? 'active' : ''}" onclick="setDaftarFilter('all')">Semua</button>`;
                allGroups.forEach(g => {
                    const short = g.replace(' Side View', ' Side').replace('Custom Items', 'Custom');
                    filterHtml += `<button class="cs-filter-btn ${daftarFilter === g ? 'active' : ''}" onclick="setDaftarFilter('${g}')">${short}</button>`;
                });
                filterHtml += `</div>`;

                let html = filterHtml;
                for (const grp in groups) {
                    if (daftarFilter !== 'all' && daftarFilter !== grp) continue;
                    html += `<div class="cs-list-group"><div class="cs-list-group-title">${grp}</div>`;
                    groups[grp].forEach(({ item, index }) => {
                        const ans = answers[item.id] || null;
                        let badgeHtml = '';
                        if (ans === 'good') badgeHtml = `<span class="cs-list-badge good">✓ GOOD</span>`;
                        else if (ans === 'bad') badgeHtml = `<span class="cs-list-badge bad">✗ BAD</span>`;
                        else if (ans === 'none') badgeHtml = `<span class="cs-list-badge none">— N/A</span>`;
                        else badgeHtml = `<span class="cs-list-badge unanswered">belum</span>`;

                        let actionBtns = badgeHtml;
                        if (canInteract) {
                            actionBtns += `<button class="cs-list-icon-btn delete" onclick="removeSpecificItem('${item.id}')" title="Hapus Item">🗑️</button>`;
                            actionBtns += `<button class="cs-list-icon-btn add" onclick="openAddModal()" title="Tambah Item Baru">➕</button>`;
                        }

                        html += `<div class="cs-list-item">
                            <div class="cs-list-item-info">
                                <div class="cs-list-item-num">${index + 1}</div>
                                <div class="cs-list-item-label">${item.label}</div>
                            </div>
                            <div class="cs-list-actions">${actionBtns}</div>
                        </div>`;
                    });
                    html += `</div>`;
                }
                container.innerHTML = html;
            }

            window.setDaftarFilter = function (filter) {
                daftarFilter = filter;
                renderDaftar();
            };

            const saveQueue = [];
            let saveBusy = false;

            async function flushSaves() {
                if (saveBusy) return;
                saveBusy = true;
                while (saveQueue.length) {
                    const job = saveQueue.shift();
                    let saved = false;
                    for (let attempt = 0; attempt < 3 && !saved; attempt++) {
                        try {
                            const r = await fetch(`/components/${COMP_ID}/checksheet/${STAGE}/answer`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': CSRF,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ item_id: job.item_id, answer: job.answer })
                            });
                            const data = await r.json().catch(() => ({}));
                            if (!r.ok) {
                                throw new Error(data.message || data.error || ('HTTP ' + r.status));
                            }
                            showToast('✓ Tersimpan');
                            saved = true;
                        } catch (e) {
                            if (attempt < 2) {
                                await new Promise(res => setTimeout(res, 250 * (attempt + 1)));
                                continue;
                            }
                            showToast('⚠ Gagal: ' + (e.message || 'jaringan'));
                        }
                    }
                }
                saveBusy = false;
            }

            window.answer = function (val) {
                if (!canInteract) return;
                const item = items[currentIndex];
                answers[item.id] = val;

                const existing = saveQueue.findIndex(j => j.item_id === item.id);
                if (existing >= 0) saveQueue.splice(existing, 1);
                saveQueue.push({ item_id: item.id, answer: val });
                flushSaves();

                setTimeout(() => {
                    currentIndex++;
                    animateSlide('right');
                }, 350);
            };

            window.navigate = function (dir) {
                const newIndex = currentIndex + dir;
                if (newIndex < 0) return;
                if (newIndex > items.length) return;
                currentIndex = newIndex;
                animateSlide(dir > 0 ? 'right' : 'left');
            };

            function animateSlide(direction) {
                const slide = document.getElementById('slideContent');
                slide.classList.remove('slide-active', 'slide-enter-right', 'slide-enter-left');
                slide.classList.add(direction === 'right' ? 'slide-enter-right' : 'slide-enter-left');

                setTimeout(() => {
                    render();
                    slide.classList.remove('slide-enter-right', 'slide-enter-left');
                    void slide.offsetWidth; // force reflow
                    slide.classList.add('slide-active');
                }, 150);
            }

            window.removeSpecificItem = function (itemId) {
                if (!confirm('Hapus item ini dari checksheet?')) return;

                fetch(`/components/${COMP_ID}/checksheet/${STAGE}/remove-item`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ item_id: itemId })
                }).then(r => r.json()).then(data => {
                    const idx = items.findIndex(i => i.id === itemId);
                    if (idx > -1) items.splice(idx, 1);
                    delete answers[itemId];
                    if (currentIndex >= items.length) currentIndex = items.length - 1;
                    if (currentIndex < 0) currentIndex = 0;
                    render();
                    showToast('🗑️ Item dihapus');
                });
            };

            window.openAddModal = function () {
                document.getElementById('addModal').style.display = 'flex';
                document.getElementById('newItemLabel').focus();
            };

            window.closeAddModal = function () {
                document.getElementById('addModal').style.display = 'none';
                document.getElementById('newItemLabel').value = '';
            };

            window.submitAddItem = function () {
                const label = document.getElementById('newItemLabel').value.trim();
                const group = document.getElementById('newItemGroup').value;
                if (!label) return;

                fetch(`/components/${COMP_ID}/checksheet/${STAGE}/add-item`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ label, group })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        items.push(data.item);
                        closeAddModal();
                        currentIndex = items.length - 1;
                        render();
                        showToast('+ Item ditambahkan');
                    }
                });
            };

            function showToast(msg) {
                const toast = document.getElementById('toast');
                toast.textContent = msg;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 1800);
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', function (e) {
                if (document.getElementById('addModal').style.display === 'flex') return;
                if (e.key === '1') answer('good');
                else if (e.key === '2') answer('bad');
                else if (e.key === '3') answer('none');
                else if (e.key === 'ArrowLeft') navigate(-1);
                else if (e.key === 'ArrowRight') navigate(1);
            });

            // Touch swipe
            let touchStartX = 0;
            document.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; });
            document.addEventListener('touchend', e => {
                const diff = e.changedTouches[0].screenX - touchStartX;
                if (Math.abs(diff) > 60) {
                    navigate(diff < 0 ? 1 : -1);
                }
            });

            // Initial render
            render();
        })();
    </script>
</body>

</html>
