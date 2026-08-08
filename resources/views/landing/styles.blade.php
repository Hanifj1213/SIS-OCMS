    <style>
        /* ====================================================
           SIS-OCMS LANDING PAGE — WORLD-CLASS DESIGN SYSTEM
        ==================================================== */
        :root {
            --bg-deep: #060F0D;
            --bg-primary: #0B2B26;
            --bg-secondary: #091528;
            --bg-navy: #0d1f3c;
            --accent-gold: #D4AF37;
            --accent-gold-hover: #EAA112;
            --accent-gold-dim: rgba(212, 175, 55, 0.12);
            --accent-cyan: #48CAE4;
            --accent-cyan-dim: rgba(72, 202, 228, 0.08);
            --accent-green: #34D399;
            --accent-green-dim: rgba(52, 211, 153, 0.10);
            --glass-bg: rgba(255, 255, 255, 0.025);
            --glass-border: rgba(255, 255, 255, 0.06);
            --glass-border-light: rgba(255, 255, 255, 0.10);
            --text-primary: rgba(255, 255, 255, 0.92);
            --text-secondary: rgba(255, 255, 255, 0.50);
            --text-muted: rgba(255, 255, 255, 0.22);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            scroll-behavior: auto; /* GSAP handles smooth scroll */
            overflow-x: hidden;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-deep);
            color: var(--text-primary);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(212,175,55,0.2); border-radius: 3px; }

        /* ============ THREE.JS CANVAS ============ */
        #hero-canvas {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        /* ============ GLOBAL HELPERS ============ */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }
        .reveal {
            opacity: 0;
            transform: translateY(60px);
        }

        /* ============ NAVBAR ============ */
        .nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 20px 0;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .nav.scrolled {
            background: rgba(6, 15, 13, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 12px 0;
        }
        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            min-height: 44px;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            flex-shrink: 0;
            min-width: 0;
        }
        .nav-brand-logo {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            line-height: 0;
        }
        .nav-brand-logo .alamtri-logo-full {
            height: 38px;
            width: auto;
            object-fit: contain;
            display: block;
            mix-blend-mode: lighten;
        }
        .nav-brand-name {
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            white-space: nowrap;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
            flex-shrink: 0;
        }
        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
        }
        .nav-links a:hover { color: var(--text-primary); }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 0;
            width: 0; height: 2px;
            background: var(--accent-gold);
            border-radius: 1px;
            transition: width 0.3s;
        }
        .nav-links a:hover::after { width: 100%; }
        .nav-cta {
            padding: 10px 24px;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover)) !important;
            color: var(--bg-primary) !important;
            font-weight: 700 !important;
            font-size: 0.85rem;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 16px rgba(212, 175, 55, 0.2);
        }
        .nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(212, 175, 55, 0.35);
        }
        .nav-cta::after { display: none !important; }

        /* ============ HERO SECTION ============ */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 88px;
            padding-bottom: 64px;
            box-sizing: border-box;
            overflow: hidden;
            background: radial-gradient(ellipse at 50% 45%, rgba(11, 43, 38, 0.8) 0%, var(--bg-deep) 70%);
        }
        .hero > .container {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 760px;
            width: 100%;
            margin: 0 auto;
            text-align: left;
        }
        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: var(--accent-gold-dim);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 100px;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--accent-gold);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 28px;
            max-width: 100%;
        }
        .hero-eyebrow .dot {
            width: 6px; height: 6px;
            background: var(--accent-gold);
            border-radius: 50%;
            animation: pulse-dot 2s ease infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }
        .hero-title {
            font-size: clamp(2.8rem, 5.5vw, 4.5rem);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.04em;
            margin-bottom: 24px;
        }
        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 50%, var(--accent-cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-subtitle {
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--text-secondary);
            max-width: 560px;
            margin: 0 0 40px;
        }
        .hero-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
        }
        .btn-hero-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 18px 36px;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover));
            color: var(--bg-primary);
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-hero-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 16px 48px rgba(212, 175, 55, 0.4);
        }
        .btn-hero-primary::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transition: left 0.6s;
        }
        .btn-hero-primary:hover::after { left: 100%; }
        .btn-hero-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 18px 32px;
            background: transparent;
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid var(--glass-border-light);
            border-radius: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-hero-secondary:hover {
            color: var(--text-primary);
            background: var(--glass-bg);
            border-color: rgba(255,255,255,0.15);
        }

        /* Hero Stats Row — tampil langsung saat load (tanpa scroll trigger) */
        .hero-stats {
            display: flex;
            gap: 48px;
            margin-top: 48px;
            padding-top: 28px;
            border-top: 1px solid var(--glass-border);
            justify-content: flex-start;
            flex-wrap: wrap;
            opacity: 1;
            transform: none;
        }
        .hero-stat-value {
            font-size: 2rem;
            font-weight: 900;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: -0.03em;
        }
        .hero-stat-value.gold { color: var(--accent-gold); }
        .hero-stat-value.cyan { color: var(--accent-cyan); }
        .hero-stat-value.green { color: var(--accent-green); }
        .hero-stat-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 4px;
            font-weight: 600;
        }

        /* Glow Orbs */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(140px);
            pointer-events: none;
        }
        .glow-orb-gold {
            width: 600px; height: 600px;
            background: var(--accent-gold);
            opacity: 0.06;
            top: 10%; right: 5%;
        }
        .glow-orb-cyan {
            width: 400px; height: 400px;
            background: var(--accent-cyan);
            opacity: 0.04;
            bottom: 10%; left: 10%;
        }

        /* Grid Pattern */
        .grid-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: 0.025;
            background-image:
                linear-gradient(rgba(255,255,255,0.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.15) 1px, transparent 1px);
            background-size: 80px 80px;
            mask-image: radial-gradient(ellipse at 40% 50%, black 20%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at 40% 50%, black 20%, transparent 70%);
        }

        /* ============ SECTION DIVIDER ============ */
        .section-divider {
            width: 100%;
            height: 200px;
            position: relative;
            margin-top: -100px;
            z-index: 5;
            background: linear-gradient(180deg, transparent 0%, var(--bg-deep) 100%);
            pointer-events: none;
        }

        /* ============ FEATURES / BENTO GRID ============ */
        .features-section {
            position: relative;
            padding: 120px 0 100px;
            background: linear-gradient(180deg, var(--bg-deep) 0%, var(--bg-primary) 30%, var(--bg-secondary) 100%);
        }
        .section-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--accent-gold);
            margin-bottom: 16px;
        }
        .section-heading {
            font-size: clamp(2rem, 3.5vw, 3rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 16px;
        }
        .section-description {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 520px;
            line-height: 1.7;
            margin-bottom: 64px;
        }

        /* Bento Grid */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: auto auto;
            gap: 16px;
        }
        .bento-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px 36px;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bento-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
        }
        .bento-card:hover {
            background: rgba(255,255,255,0.04);
            border-color: var(--glass-border-light);
            transform: translateY(-4px);
            box-shadow: 0 32px 64px rgba(0,0,0,0.3);
        }
        .bento-card.large {
            grid-column: span 2;
            padding: 48px 44px;
        }
        .bento-card.tall {
            grid-row: span 2;
        }
        .bento-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 24px;
        }
        .bento-icon.gold { background: var(--accent-gold-dim); }
        .bento-icon.cyan { background: var(--accent-cyan-dim); }
        .bento-icon.green { background: var(--accent-green-dim); }
        .bento-title {
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .bento-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }
        .bento-tag {
            display: inline-block;
            margin-top: 20px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .bento-tag.gold { background: var(--accent-gold-dim); color: var(--accent-gold); }
        .bento-tag.cyan { background: var(--accent-cyan-dim); color: var(--accent-cyan); }
        .bento-tag.green { background: var(--accent-green-dim); color: var(--accent-green); }

        /* Mini visual inside bento card */
        .bento-visual {
            margin-top: 24px;
            display: flex;
            gap: 6px;
            align-items: flex-end;
        }
        .bento-bar {
            flex: 1;
            border-radius: 6px 6px 0 0;
            opacity: 0.6;
            transition: opacity 0.3s;
        }
        .bento-card:hover .bento-bar { opacity: 1; }

        /* ============ WORKFLOW / TIMELINE ============ */
        .workflow-section {
            position: relative;
            padding: 120px 0 140px;
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-deep) 100%);
        }
        .timeline-wrapper {
            position: relative;
            margin-top: 80px;
        }
        .timeline-lines {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        /* Vertical Line — always behind cards & nodes */
        .timeline-line {
            position: absolute;
            left: 50%;
            top: 0; bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, var(--accent-gold) 0%, var(--accent-cyan) 50%, var(--accent-green) 100%);
            opacity: 0.15;
            transform: translateX(-50%);
            pointer-events: none;
        }
        .timeline-line-glow {
            position: absolute;
            left: 50%;
            top: 0;
            width: 2px;
            height: 0;
            background: linear-gradient(180deg, var(--accent-gold), var(--accent-cyan));
            transform: translateX(-50%);
            pointer-events: none;
            opacity: 0.85;
        }
        .timeline-track {
            position: relative;
            z-index: 1;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            position: relative;
            margin-bottom: 56px;
        }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-item:nth-child(odd) { flex-direction: row; }
        .timeline-item:nth-child(even) { flex-direction: row-reverse; }

        .timeline-content {
            width: calc(50% - 48px);
            background: rgba(9, 21, 40, 0.82);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 28px 32px;
            transition: all 0.4s;
            position: relative;
            z-index: 2;
        }
        .timeline-content:hover {
            background: rgba(13, 31, 60, 0.95);
            border-color: var(--glass-border-light);
            transform: translateY(-4px);
            box-shadow: 0 24px 48px rgba(0,0,0,0.25);
        }

        .timeline-node {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 44px; height: 44px;
            border-radius: 14px;
            background: var(--bg-deep);
            border: 2px solid var(--glass-border-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.85rem;
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent-gold);
            z-index: 3;
            transition: border-color 0.3s, box-shadow 0.3s, background 0.3s;
            box-shadow: 0 0 0 5px var(--bg-secondary);
        }
        .timeline-node::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 18px;
            background: var(--bg-secondary);
            z-index: -1;
        }
        .timeline-spacer {
            width: calc(50% - 48px);
            flex-shrink: 0;
        }
        .timeline-item:hover {
            z-index: 2;
        }
        .timeline-item:hover .timeline-node {
            background: #101c19;
            border-color: var(--accent-gold);
            box-shadow:
                0 0 0 5px var(--bg-secondary),
                0 0 0 7px rgba(212, 175, 55, 0.12),
                0 0 28px rgba(212, 175, 55, 0.35);
        }

        .timeline-stage-name {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }
        .timeline-stage-desc {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        .timeline-stage-num {
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 6px;
        }

        /* ============ CTA / LOGIN SECTION ============ */
        .cta-section {
            position: relative;
            padding: 140px 0 120px;
            background: radial-gradient(ellipse at 50% 100%, rgba(11, 43, 38, 0.6) 0%, var(--bg-deep) 70%);
            overflow: hidden;
        }
        .cta-content {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        .cta-headline {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 900;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
        }
        .cta-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 480px;
            margin: 0 auto 48px;
            line-height: 1.7;
        }

        /* Login Form Card */
        .login-form-card {
            max-width: 420px;
            margin: 0 auto;
            background: var(--glass-bg);
            backdrop-filter: blur(40px) saturate(150%);
            -webkit-backdrop-filter: blur(40px) saturate(150%);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 44px 40px;
            text-align: left;
            box-shadow: 0 32px 80px rgba(0,0,0,0.4), 0 0 120px rgba(212, 175, 55, 0.03);
            position: relative;
        }
        .login-form-card::before {
            content: '';
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(212,175,55,0.2), transparent);
        }
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .form-input {
            width: 100%;
            padding: 15px 18px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s;
        }
        .form-input::placeholder { color: var(--text-muted); font-family: 'Inter', sans-serif; }
        .form-input:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1), 0 0 24px rgba(212, 175, 55, 0.05);
            background: rgba(255,255,255,0.06);
        }
        .form-error { font-size: 0.75rem; color: #ff6b6b; margin-top: 6px; }
        .form-remember {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .form-remember input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--accent-gold);
            cursor: pointer;
        }
        .form-remember label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            cursor: pointer;
        }
        .btn-login {
            width: 100% !important;
            padding: 16px !important;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%) !important;
            border: none !important;
            border-radius: 12px !important;
            color: var(--bg-primary) !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 0.9rem !important;
            font-weight: 700 !important;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 16px rgba(212, 175, 55, 0.25);
            display: block !important;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(212, 175, 55, 0.35);
        }
        .form-footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* CTA glow */
        .cta-glow {
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: var(--accent-gold);
            opacity: 0.04;
            filter: blur(150px);
            bottom: -200px; left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
        }

        /* ============ FOOTER ============ */
        .footer {
            padding: 40px 0;
            border-top: 1px solid var(--glass-border);
            text-align: center;
        }
        .footer p {
            font-size: 0.72rem;
            color: var(--text-muted);
            line-height: 1.8;
        }
        .footer a {
            color: var(--accent-gold);
            text-decoration: none;
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 1024px) {
            .bento-grid { grid-template-columns: repeat(2, 1fr); }
            .bento-card.large { grid-column: span 2; }
            .bento-card.tall { grid-row: span 1; }
        }
        @media (max-width: 768px) {
            .container { padding: 0 20px; }
            .nav-links { display: none; }
            .nav { padding: 14px 0; }
            .nav-brand-logo .alamtri-logo-full { height: 30px; }
            .hero { min-height: auto; padding: 96px 0 72px; align-items: center; }
            .hero-subtitle { margin: 0 0 28px; }
            .hero-buttons { justify-content: flex-start; }
            .hero-title { font-size: clamp(2rem, 9vw, 2.6rem); margin-bottom: 18px; }
            .hero-subtitle { font-size: 0.95rem; margin-bottom: 28px; }
            .hero-stats {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 20px 28px;
                margin-top: 40px;
                padding-top: 24px;
            }
            .hero-stat-value { font-size: 1.5rem; }
            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                gap: 12px;
            }
            .btn-hero-primary,
            .btn-hero-secondary {
                width: 100%;
                justify-content: center;
                padding: 16px 24px;
            }
            .features-section,
            .workflow-section,
            .cta-section { padding: 72px 0; }
            .section-heading { font-size: clamp(1.6rem, 7vw, 2rem); }
            .section-description { margin-bottom: 40px; font-size: 0.92rem; }
            .bento-grid { grid-template-columns: 1fr; }
            .bento-card,
            .bento-card.large { grid-column: span 1; padding: 28px 24px; }
            .timeline-wrapper { margin-top: 48px; }
            .timeline-item,
            .timeline-item:nth-child(even) {
                flex-direction: column !important;
                align-items: stretch;
                margin-bottom: 40px;
                padding-top: 0;
            }
            .timeline-spacer { display: none; }
            .timeline-content {
                width: 100%;
                padding: 36px 22px 24px;
                margin-top: -22px;
            }
            .timeline-node {
                position: relative;
                left: auto;
                transform: none;
                align-self: center;
                z-index: 3;
                box-shadow: 0 0 0 5px var(--bg-secondary);
            }
            .timeline-item:hover .timeline-node {
                box-shadow:
                    0 0 0 5px var(--bg-secondary),
                    0 0 0 7px rgba(212, 175, 55, 0.12),
                    0 0 28px rgba(212, 175, 55, 0.35);
            }
            .login-form-card { padding: 32px 24px; }
            .skew-scroll { transform: none !important; }
        }

        /* ============ VELOCITY SCROLL EFFECT ============ */
        .skew-scroll {
            transition: transform 0.3s ease-out;
            will-change: transform;
        }
    </style>
