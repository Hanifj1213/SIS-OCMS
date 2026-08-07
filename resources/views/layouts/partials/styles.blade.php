        <style>
            /* ============================================
               SIS-OCMS PREMIUM DESIGN SYSTEM
               Dark / Light Mode — Corporate Adaro Palette
               --ink = komponen RGB "tinta"; semua warna teks/garis
               transparan diturunkan dari sini agar ikut berganti tema.
            ============================================ */
            :root {
                --ink: 255, 255, 255;
                --bg-primary: #0B2B26;
                --bg-secondary: #091528;
                --bg-tertiary: #0d1f3c;
                --bg-card: rgba(var(--ink), 0.03);
                --bg-card-hover: rgba(var(--ink), 0.06);
                --accent-gold: #D4AF37;
                --accent-gold-dim: rgba(212, 175, 55, 0.15);
                --accent-gold-hover: #EAA112;
                --accent-cyan: #48CAE4;
                --accent-cyan-dim: rgba(72, 202, 228, 0.12);
                --accent-green: #34D399;
                --accent-green-dim: rgba(52, 211, 153, 0.12);
                --accent-red: #F87171;
                --accent-red-dim: rgba(248, 113, 113, 0.12);
                --accent-purple: #A78BFA;
                --accent-purple-dim: rgba(167, 139, 250, 0.12);
                --glass-border: rgba(var(--ink), 0.08);
                --glass-border-light: rgba(var(--ink), 0.14);
                --text-primary: rgba(var(--ink), 0.95);
                --text-secondary: rgba(var(--ink), 0.72);
                --text-muted: rgba(var(--ink), 0.50);
                --shadow-heavy: 0 24px 48px rgba(0, 0, 0, 0.3);
                --nav-bg: rgba(11, 43, 38, 0.6);
                --nav-bg-solid: rgba(11, 43, 38, 0.95);
                --on-accent: #0B2B26;
                --select-option-bg: #0B2B26;
            }

            html[data-theme="light"] {
                --ink: 12, 35, 30;
                --bg-primary: #E7EDEA;
                --bg-secondary: #E0E7EF;
                --bg-tertiary: #D9E1EC;
                --bg-card: rgba(255, 255, 255, 0.88);
                --bg-card-hover: #FFFFFF;
                --accent-gold: #92580A;
                --accent-gold-dim: rgba(146, 88, 10, 0.14);
                --accent-gold-hover: #A9650C;
                --accent-cyan: #0C637C;
                --accent-cyan-dim: rgba(12, 99, 124, 0.12);
                --accent-green: #036B4F;
                --accent-green-dim: rgba(3, 107, 79, 0.12);
                --accent-red: #C71F1F;
                --accent-red-dim: rgba(199, 31, 31, 0.12);
                --accent-purple: #5B21B6;
                --accent-purple-dim: rgba(91, 33, 182, 0.12);
                --glass-border: rgba(var(--ink), 0.16);
                --glass-border-light: rgba(var(--ink), 0.24);
                --text-primary: rgba(var(--ink), 0.95);
                --text-secondary: rgba(var(--ink), 0.80);
                --text-muted: rgba(var(--ink), 0.62);
                --shadow-heavy: 0 24px 48px rgba(12, 35, 30, 0.12);
                --nav-bg: rgba(255, 255, 255, 0.82);
                --nav-bg-solid: rgba(255, 255, 255, 0.98);
                --on-accent: #FFFFFF;
                --select-option-bg: #FFFFFF;
            }

            * { box-sizing: border-box; }

            html {
                scroll-behavior: smooth;
            }

            body.ocms-body {
                font-family: 'Inter', -apple-system, sans-serif;
                background: linear-gradient(170deg, var(--bg-primary) 0%, var(--bg-secondary) 40%, var(--bg-tertiary) 100%);
                background-attachment: fixed;
                color: var(--text-primary);
                min-height: 100vh;
                overflow-x: hidden;
            }

            /* ============ NAVBAR ============ */
            .ocms-nav {
                position: sticky;
                top: 0;
                z-index: 100;
                background: var(--nav-bg);
                backdrop-filter: blur(24px) saturate(180%);
                -webkit-backdrop-filter: blur(24px) saturate(180%);
                border-bottom: 1px solid var(--glass-border);
            }
            .ocms-nav-inner {
                max-width: 1400px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 32px;
                height: 64px;
            }
            .ocms-nav-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                text-decoration: none;
            }
            .ocms-nav-brand-logo {
                flex-shrink: 0;
                display: flex;
                align-items: center;
                line-height: 0;
            }
            .ocms-nav-brand-logo .alamtri-logo-full {
                height: 34px;
                width: auto;
                max-width: none;
                object-fit: contain;
                display: block;
            }
            html:not([data-theme="light"]) .ocms-nav-brand-logo .alamtri-logo-full,
            html:not([data-theme="light"]) .nav-brand-logo .alamtri-logo-full {
                mix-blend-mode: lighten;
            }
            html[data-theme="light"] .ocms-nav-brand-logo .alamtri-logo-full {
                filter: none;
            }
            .ocms-nav-brand-text {
                font-weight: 800;
                font-size: 1.1rem;
                letter-spacing: -0.02em;
                color: var(--text-primary);
            }
            .ocms-nav-brand-text span {
                font-weight: 400;
                color: var(--text-secondary);
                font-size: 0.75rem;
                display: block;
                margin-top: -2px;
            }

            /* Nav Links */
            .ocms-nav-links {
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .ocms-nav-link {
                padding: 8px 16px;
                border-radius: 10px;
                font-size: 0.8rem;
                font-weight: 500;
                color: var(--text-secondary);
                text-decoration: none;
                transition: all 0.25s ease;
                position: relative;
            }
            .ocms-nav-link:hover {
                color: var(--text-primary);
                background: rgba(var(--ink), 0.05);
            }
            .ocms-nav-link.active {
                color: var(--accent-gold);
                background: var(--accent-gold-dim);
            }

            /* Nav User */
            .ocms-nav-user {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .ocms-nav-avatar {
                width: 36px; height: 36px;
                border-radius: 10px;
                background: linear-gradient(135deg, #1a3a4a, #0B2B26);
                border: 1px solid var(--glass-border-light);
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 0.75rem;
                color: var(--accent-gold);
            }
            .ocms-nav-username {
                font-size: 0.8rem;
                font-weight: 600;
                color: var(--text-primary);
            }
            .ocms-nav-role {
                font-size: 0.65rem;
                color: var(--accent-gold);
                font-weight: 500;
            }
            .ocms-nav-logout {
                padding: 6px 14px;
                border-radius: 8px;
                background: rgba(248, 113, 113, 0.1);
                border: 1px solid rgba(248, 113, 113, 0.15);
                color: var(--accent-red);
                font-size: 0.75rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                font-family: 'Inter', sans-serif;
            }
            .ocms-nav-logout:hover {
                background: rgba(248, 113, 113, 0.2);
            }
            .ocms-mobile-logout {
                margin-top: 8px;
                padding-top: 12px;
                border-top: 1px solid var(--glass-border);
            }
            .ocms-mobile-logout .ocms-nav-logout {
                width: 100%;
                padding: 12px 16px;
                font-size: 0.85rem;
            }

            /* Welcome + role badge */
            .ocms-welcome-role {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 0;
            }
            .ocms-welcome-role .ocms-role-badge {
                margin-top: 10px;
                margin-left: -2px;
            }

            /* ============ MAIN CONTENT ============ */
            .ocms-main {
                max-width: 1400px;
                margin: 0 auto;
                padding: 40px 32px 80px;
                position: relative;
            }

            /* Page Header */
            .ocms-page-header {
                margin-bottom: 40px;
                position: relative;
            }
            .ocms-page-header h1 {
                font-size: 2rem;
                font-weight: 800;
                letter-spacing: -0.03em;
                color: var(--text-primary);
                margin-bottom: 6px;
            }
            .ocms-page-header p {
                font-size: 0.9rem;
                color: var(--text-secondary);
            }

            /* ============ GLASS CARD ============ */
            .glass-card {
                background: var(--bg-card);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid var(--glass-border);
                border-radius: 20px;
                padding: 28px;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }
            .glass-card::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(var(--ink), 0.08), transparent);
            }
            .glass-card:hover {
                background: var(--bg-card-hover);
                border-color: var(--glass-border-light);
                transform: translateY(-2px);
                box-shadow: var(--shadow-heavy);
            }

            /* Interactive Card (links) */
            a.glass-card {
                text-decoration: none;
                display: block;
            }

            /* ============ METRIC CARDS ============ */
            .metric-card {
                text-align: center;
                padding: 32px 20px;
            }
            .metric-card .metric-icon {
                width: 48px; height: 48px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 16px;
                font-size: 1.3rem;
            }
            .metric-card .metric-label {
                font-size: 0.65rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                margin-bottom: 8px;
            }
            .metric-card .metric-value {
                font-size: 2.8rem;
                font-weight: 900;
                letter-spacing: -0.04em;
                line-height: 1;
                margin-bottom: 4px;
                font-family: 'JetBrains Mono', monospace;
            }
            .metric-card .metric-sub {
                font-size: 0.7rem;
                color: var(--text-muted);
            }

            /* Metric Color Variants */
            .metric-gold .metric-icon { background: var(--accent-gold-dim); color: var(--accent-gold); }
            .metric-gold .metric-label { color: var(--accent-gold); }
            .metric-gold .metric-value { color: var(--accent-gold); }

            .metric-green .metric-icon { background: var(--accent-green-dim); color: var(--accent-green); }
            .metric-green .metric-label { color: var(--accent-green); }
            .metric-green .metric-value { color: var(--accent-green); }

            .metric-cyan .metric-icon { background: var(--accent-cyan-dim); color: var(--accent-cyan); }
            .metric-cyan .metric-label { color: var(--accent-cyan); }
            .metric-cyan .metric-value { color: var(--accent-cyan); }

            .metric-red .metric-icon { background: var(--accent-red-dim); color: var(--accent-red); }
            .metric-red .metric-label { color: var(--accent-red); }
            .metric-red .metric-value { color: var(--accent-red); }

            /* ============ STAGE BAR ============ */
            .stage-bar { display: flex; align-items: center; gap: 6px; }
            .stage-node {
                flex: 1;
                text-align: center;
                padding: 12px 4px;
                border-radius: 12px;
                font-size: 0.7rem;
                font-weight: 700;
                transition: all 0.3s;
            }
            .stage-node.completed { background: var(--accent-green-dim); color: var(--accent-green); }
            .stage-node.active { background: var(--accent-cyan-dim); color: var(--accent-cyan); box-shadow: 0 0 20px rgba(72, 202, 228, 0.15); }
            .stage-node.pending { background: rgba(var(--ink), 0.02); color: var(--text-muted); }
            .stage-connector {
                width: 16px; height: 2px;
                border-radius: 1px;
            }
            .stage-connector.done { background: var(--accent-green); opacity: 0.3; }
            .stage-connector.undone { background: rgba(var(--ink), 0.06); }

            /* ============ MODULE CARDS ============ */
            .module-card {
                padding: 36px 28px;
                position: relative;
            }
            .module-card .module-icon {
                font-size: 2rem;
                margin-bottom: 16px;
                display: block;
            }
            .module-card h3 {
                font-size: 1.15rem;
                font-weight: 700;
                letter-spacing: -0.02em;
                margin-bottom: 8px;
                color: var(--text-primary);
            }
            .module-card p {
                font-size: 0.8rem;
                color: var(--text-secondary);
                line-height: 1.6;
            }
            .module-card .module-arrow {
                position: absolute;
                top: 36px; right: 28px;
                width: 32px; height: 32px;
                border-radius: 8px;
                background: rgba(var(--ink), 0.04);
                border: 1px solid var(--glass-border);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--text-muted);
                transition: all 0.3s;
            }
            a.glass-card:hover .module-arrow {
                background: var(--accent-gold-dim);
                color: var(--accent-gold);
                border-color: rgba(212, 175, 55, 0.2);
                transform: translateX(4px);
            }

            /* ============ TABLE STYLES ============ */
            .ocms-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }
            .ocms-table thead th {
                font-size: 0.65rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: var(--text-muted);
                padding: 12px 16px;
                text-align: left;
                border-bottom: 1px solid var(--glass-border);
            }
            .ocms-table tbody td {
                padding: 14px 16px;
                font-size: 0.85rem;
                color: var(--text-secondary);
                border-bottom: 1px solid rgba(var(--ink), 0.02);
                transition: background 0.2s;
            }
            .ocms-table tbody tr:hover td {
                background: rgba(var(--ink), 0.02);
            }
            .ocms-table .mono {
                font-family: 'JetBrains Mono', monospace;
                font-weight: 600;
                color: var(--text-primary);
            }

            /* ============ BADGES ============ */
            .badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 4px 10px;
                border-radius: 8px;
                font-size: 0.7rem;
                font-weight: 600;
                white-space: nowrap;
            }
            .badge-gold { background: var(--accent-gold-dim); color: var(--accent-gold); }
            .badge-green { background: var(--accent-green-dim); color: var(--accent-green); }
            .badge-red { background: var(--accent-red-dim); color: var(--accent-red); }
            .badge-cyan { background: var(--accent-cyan-dim); color: var(--accent-cyan); }
            .badge-purple { background: var(--accent-purple-dim); color: var(--accent-purple); }

            /* ============ BUTTONS ============ */
            .btn-primary {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover));
                border: none;
                border-radius: 12px;
                color: var(--on-accent);
                font-family: 'Inter', sans-serif;
                font-size: 0.85rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 4px 16px rgba(212, 175, 55, 0.2);
                text-decoration: none;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
            }
            .btn-secondary {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: rgba(var(--ink), 0.04);
                border: 1px solid var(--glass-border);
                border-radius: 12px;
                color: var(--text-secondary);
                font-family: 'Inter', sans-serif;
                font-size: 0.85rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
            }
            .btn-secondary:hover {
                background: rgba(var(--ink), 0.08);
                color: var(--text-primary);
            }
            .btn-danger {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: var(--accent-red-dim);
                border: 1px solid rgba(248, 113, 113, 0.2);
                border-radius: 12px;
                color: var(--accent-red);
                font-family: 'Inter', sans-serif;
                font-size: 0.85rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
            }
            .btn-danger:hover {
                background: rgba(248, 113, 113, 0.2);
            }
            .btn-sm {
                padding: 6px 14px;
                font-size: 0.75rem;
                border-radius: 8px;
            }

            /* ============ FORM INPUTS (DARK) ============ */
            .ocms-input {
                width: 100%;
                padding: 14px 16px;
                background: rgba(var(--ink), 0.04);
                border: 1px solid var(--glass-border);
                border-radius: 12px;
                color: var(--text-primary);
                font-family: 'Inter', sans-serif;
                font-size: 0.9rem;
                transition: all 0.3s;
                outline: none;
            }
            .ocms-input:focus {
                border-color: var(--accent-gold);
                box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
                background: rgba(var(--ink), 0.06);
            }
            .ocms-input::placeholder { color: var(--text-muted); }

            .ocms-select {
                width: 100%;
                padding: 14px 16px;
                background: rgba(var(--ink), 0.04);
                border: 1px solid var(--glass-border);
                border-radius: 12px;
                color: var(--text-primary);
                font-family: 'Inter', sans-serif;
                font-size: 0.9rem;
                transition: all 0.3s;
                outline: none;
                -webkit-appearance: none;
            }
            .ocms-select option { background: var(--select-option-bg); color: var(--text-primary); }
            .ocms-select:focus {
                border-color: var(--accent-gold);
                box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            }

            .ocms-label {
                display: block;
                font-size: 0.75rem;
                font-weight: 600;
                color: var(--text-secondary);
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            /* ============ ALERTS ============ */
            .alert {
                padding: 16px 20px;
                border-radius: 14px;
                font-size: 0.85rem;
                margin-bottom: 24px;
                border: 1px solid;
            }
            .alert-success {
                background: var(--accent-green-dim);
                border-color: rgba(52, 211, 153, 0.2);
                color: var(--accent-green);
            }
            .alert-error {
                background: var(--accent-red-dim);
                border-color: rgba(248, 113, 113, 0.2);
                color: var(--accent-red);
            }

            /* ============ GRID HELPERS ============ */
            .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
            .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
            .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
            .grid-8 { display: grid; grid-template-columns: repeat(8, 1fr); gap: 8px; }

            /* Grid apa pun (termasuk yang pakai inline style) jadi 1 kolom di HP */
            @media (max-width: 768px) {
                .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
                .grid-8 { grid-template-columns: repeat(4, 1fr); }
                .ocms-nav-links { display: none; }
                .ocms-main { padding: 16px 12px 60px; }
                .ocms-nav-inner { padding: 0 16px; }
                .section { margin-bottom: 28px; }
                .ocms-page-header h1 { font-size: 1.35rem; }
                .stack-mobile { grid-template-columns: 1fr !important; }

                /* Stage bar: 7 node dibungkus jadi 2 baris, konektor disembunyikan */
                .stage-bar { flex-wrap: wrap; gap: 8px; }
                .stage-connector { display: none; }
                .stage-node { flex: 1 1 calc(25% - 8px); min-width: 76px; padding: 10px 2px; }

                .glass-card { border-radius: 14px; }
                .metric-card .metric-value { font-size: 1.4rem; }
            }

            /* ============ SECTION SPACING ============ */
            .section { margin-bottom: 40px; }
            .section-title {
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                color: var(--text-muted);
                margin-bottom: 20px;
            }

            /* ============ 3D CANVAS BG ============ */
            #three-canvas-bg {
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                z-index: 0;
                pointer-events: none;
                opacity: 0.5;
            }

            /* Grid Overlay */
            .grid-overlay {
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                z-index: 0;
                pointer-events: none;
                opacity: 0.02;
                background-image:
                    linear-gradient(rgba(var(--ink), 0.1) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(var(--ink), 0.1) 1px, transparent 1px);
                background-size: 80px 80px;
            }

            /* ============ ANIMATIONS ============ */
            .fade-up {
                opacity: 0;
                transform: translateY(30px);
            }

            /* Scrollbar */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: rgba(var(--ink), 0.1); border-radius: 3px; }
            ::-webkit-scrollbar-thumb:hover { background: rgba(var(--ink), 0.2); }

            /* Theme toggle */
            .ocms-theme-btn {
                background: rgba(var(--ink), 0.04);
                border: 1px solid var(--glass-border);
                border-radius: 8px;
                padding: 6px 10px;
                cursor: pointer;
                font-size: 0.9rem;
                line-height: 1;
                transition: all 0.2s;
            }
            .ocms-theme-btn:hover { background: rgba(var(--ink), 0.08); }
            .theme-icon-light { display: none; }
            html[data-theme="light"] .theme-icon-dark { display: none; }
            html[data-theme="light"] .theme-icon-light { display: inline; }

            /* Mobile menu */
            .mobile-menu-btn {
                display: none;
                background: none;
                border: 1px solid var(--glass-border);
                border-radius: 8px;
                padding: 8px;
                color: var(--text-secondary);
                cursor: pointer;
                font-size: 1rem;
                line-height: 1;
            }
            .ocms-mobile-menu {
                display: none;
                flex-direction: column;
                gap: 4px;
                padding: 12px 16px 16px;
                border-top: 1px solid var(--glass-border);
                background: var(--nav-bg-solid);
                backdrop-filter: blur(24px);
            }
            .ocms-mobile-menu.open { display: flex; }
            .ocms-mobile-menu .ocms-nav-link { display: block; padding: 12px 16px; }
            @media (max-width: 768px) {
                .mobile-menu-btn { display: flex; }
                .ocms-nav-user .ocms-nav-username,
                .ocms-nav-user .ocms-nav-role { display: none; }
                .ocms-nav-inner {
                    padding: 0 12px;
                    height: 56px;
                    gap: 8px;
                }
                .ocms-nav-brand {
                    min-width: 0;
                    flex-shrink: 1;
                }
                .ocms-nav-brand-logo .alamtri-logo-full { height: 26px; }
                .ocms-nav-brand-text {
                    font-size: 0.88rem;
                    line-height: 1.15;
                    min-width: 0;
                }
                .ocms-nav-brand-text span {
                    display: block;
                    font-size: 0.58rem;
                    letter-spacing: 0.02em;
                    margin-top: 0;
                    line-height: 1.15;
                    white-space: normal;
                }
                .ocms-nav-user { gap: 6px; flex-shrink: 0; }
                .ocms-nav-logout-form { display: none; }
            }

            /* Alert dismiss animation */
            .alert.alert-dismissing {
                opacity: 0;
                transform: translateY(-8px);
                transition: all 0.5s ease;
            }

            /* Tabel bisa discroll horizontal di layar kecil */
            .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            @media (max-width: 768px) {
                .ocms-table { min-width: 720px; }
            }
        </style>
