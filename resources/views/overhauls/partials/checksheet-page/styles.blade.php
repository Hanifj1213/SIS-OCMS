    <style>
        :root {
            --ink: 255, 255, 255;
            --bg-primary: #0B2B26;
            --bg-secondary: #091528;
            --bg-tertiary: #0d1f3c;
            --accent-gold: #D4AF37;
            --accent-gold-dim: rgba(212, 175, 55, 0.15);
            --accent-cyan: #48CAE4;
            --accent-cyan-dim: rgba(72, 202, 228, 0.12);
            --accent-green: #34D399;
            --accent-green-dim: rgba(52, 211, 153, 0.12);
            --accent-red: #F87171;
            --accent-red-dim: rgba(248, 113, 113, 0.12);
            --glass-border: rgba(var(--ink), 0.08);
            --glass-border-light: rgba(var(--ink), 0.14);
            --text-primary: rgba(var(--ink), 0.95);
            --text-secondary: rgba(var(--ink), 0.72);
            --text-muted: rgba(var(--ink), 0.50);
            --nav-bg: rgba(11, 43, 38, 0.6);
            --on-accent: #0B2B26;
        }

        html[data-theme="light"] {
            --ink: 12, 35, 30;
            --bg-primary: #E7EDEA;
            --bg-secondary: #E0E7EF;
            --bg-tertiary: #D9E1EC;
            --accent-gold: #92580A;
            --accent-gold-dim: rgba(146, 88, 10, 0.14);
            --accent-cyan: #0C637C;
            --accent-cyan-dim: rgba(12, 99, 124, 0.12);
            --accent-green: #036B4F;
            --accent-green-dim: rgba(3, 107, 79, 0.12);
            --accent-red: #C71F1F;
            --accent-red-dim: rgba(199, 31, 31, 0.12);
            --glass-border: rgba(var(--ink), 0.16);
            --glass-border-light: rgba(var(--ink), 0.24);
            --text-primary: rgba(var(--ink), 0.95);
            --text-secondary: rgba(var(--ink), 0.80);
            --text-muted: rgba(var(--ink), 0.62);
            --nav-bg: rgba(255, 255, 255, 0.82);
            --on-accent: #FFFFFF;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(170deg, var(--bg-primary) 0%, var(--bg-secondary) 40%, var(--bg-tertiary) 100%);
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
            background: var(--nav-bg);
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
            background: rgba(var(--ink), 0.06);
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
            background: rgba(var(--ink), 0.04);
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
            background: rgba(var(--ink), 0.1);
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
            background: rgba(var(--ink), 0.02);
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
            background: rgba(var(--ink), 0.1);
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
            background: rgba(var(--ink), 0.02);
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
            background: rgba(var(--ink), 0.04);
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
            background: rgba(var(--ink), 0.03);
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
            background: rgba(var(--ink), 0.08);
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
            color: rgba(var(--ink), 0.06);
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
            background: rgba(var(--ink), 0.03);
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
            background: rgba(var(--ink), 0.04);
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
            border-color: rgba(var(--ink), 0.1);
        }

        .cs-answer-btn.none:hover,
        .cs-answer-btn.none.selected {
            background: rgba(var(--ink), 0.06);
            border-color: rgba(var(--ink), 0.2);
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
            background: rgba(var(--ink), 0.04);
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cs-nav-btn:hover {
            background: rgba(var(--ink), 0.08);
            color: var(--text-primary);
        }

        .cs-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .cs-nav-btn.finish {
            background: linear-gradient(135deg, var(--accent-gold), #EAA112);
            color: var(--on-accent);
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
            background: rgba(var(--ink), 0.04);
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
            color: var(--text-primary);
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
