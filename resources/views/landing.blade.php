<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIS-OCMS — Next-Gen Overhaul Component Intelligence</title>
    <meta name="description" content="Sistem manajemen overhaul komponen alat berat berbasis digital oleh PT Saptaindra Sejati. Real-time tracking, smart inventory, dan quality gate terintegrasi.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Three.js + GSAP + ScrollTrigger (self-host: cepat di LAN, tidak tergantung CDN) -->
    <script src="{{ asset('vendor/three.min.js') }}"></script>
    <script src="{{ asset('vendor/gsap.min.js') }}"></script>
    <script src="{{ asset('vendor/ScrollTrigger.min.js') }}"></script>

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
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }
        .nav-brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(212, 175, 55, 0.25);
        }
        .nav-brand-icon svg { width: 20px; height: 20px; fill: var(--bg-primary); }
        .nav-brand-name {
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
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
            overflow: hidden;
            background: radial-gradient(ellipse at 30% 50%, rgba(11, 43, 38, 0.8) 0%, var(--bg-deep) 70%);
        }
        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 720px;
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
            max-width: 540px;
            margin-bottom: 40px;
        }
        .hero-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
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

        /* Hero Stats Row */
        .hero-stats {
            display: flex;
            gap: 48px;
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid var(--glass-border);
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

        /* Vertical Line */
        .timeline-line {
            position: absolute;
            left: 50%;
            top: 0; bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, var(--accent-gold) 0%, var(--accent-cyan) 50%, var(--accent-green) 100%);
            opacity: 0.15;
            transform: translateX(-50%);
        }
        .timeline-line-glow {
            position: absolute;
            left: 50%;
            top: 0;
            width: 2px;
            height: 0;
            background: linear-gradient(180deg, var(--accent-gold), var(--accent-cyan));
            transform: translateX(-50%);
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
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 28px 32px;
            transition: all 0.4s;
        }
        .timeline-content:hover {
            background: rgba(255,255,255,0.04);
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
            z-index: 2;
            transition: all 0.3s;
        }
        .timeline-item:hover .timeline-node {
            background: var(--accent-gold-dim);
            border-color: var(--accent-gold);
            box-shadow: 0 0 24px rgba(212, 175, 55, 0.2);
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
            .hero-stats { flex-direction: column; gap: 24px; }
            .bento-grid { grid-template-columns: 1fr; }
            .bento-card.large { grid-column: span 1; }
            .timeline-item,
            .timeline-item:nth-child(even) {
                flex-direction: column !important;
                align-items: center;
            }
            .timeline-content { width: 100%; }
            .timeline-line { display: none; }
            .timeline-node { position: static; transform: none; margin-bottom: 12px; }
            .hero-buttons { flex-direction: column; align-items: flex-start; }
            .login-form-card { padding: 32px 24px; }
        }

        /* ============ VELOCITY SCROLL EFFECT ============ */
        .skew-scroll {
            transition: transform 0.3s ease-out;
            will-change: transform;
        }
    </style>
</head>
<body>

    <!-- ==================== NAVBAR ==================== -->
    <nav class="nav" id="mainNav">
        <div class="container nav-inner">
            <a href="{{ url('/') }}" class="nav-brand">
                <div class="nav-brand-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <span class="nav-brand-name">SIS-OCMS</span>
            </a>
            <ul class="nav-links">
                <li><a href="#features">Fitur</a></li>
                <li><a href="#workflow">Alur Kerja</a></li>
                <li><a href="#access">Akses</a></li>
                <li><a href="{{ route('login') }}" class="nav-cta">Login →</a></li>
            </ul>
        </div>
    </nav>

    <!-- ==================== HERO ==================== -->
    <section class="hero" id="hero">
        <div class="glow-orb glow-orb-gold"></div>
        <div class="glow-orb glow-orb-cyan"></div>
        <div class="grid-bg"></div>
        <canvas id="hero-canvas"></canvas>

        <div class="container">
            <div class="hero-content">
                <div class="hero-eyebrow reveal">
                    <span class="dot"></span>
                    PT Saptaindra Sejati — Plant Rebuild Centre
                </div>

                <h1 class="hero-title reveal">
                    Next-Gen<br>
                    <span class="gradient-text">Component Overhaul</span><br>
                    Intelligence
                </h1>

                <p class="hero-subtitle reveal">
                    Sistem manajemen overhaul komponen alat berat yang sepenuhnya digital. 
                    Real-time tracking, quality gate terintegrasi, dan smart inventory — 
                    mengurangi downtime hingga nol.
                </p>

                <div class="hero-buttons reveal">
                    <a href="#access" class="btn-hero-primary">
                        Access System
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#workflow" class="btn-hero-secondary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="10,8 16,12 10,16"/></svg>
                        Lihat Alur Kerja
                    </a>
                </div>

                <div class="hero-stats reveal">
                    <div>
                        <div class="hero-stat-value gold">8</div>
                        <div class="hero-stat-label">Tahapan Overhaul</div>
                    </div>
                    <div>
                        <div class="hero-stat-value cyan">100%</div>
                        <div class="hero-stat-label">Paperless System</div>
                    </div>
                    <div>
                        <div class="hero-stat-value green">24/7</div>
                        <div class="hero-stat-label">Real-time Monitoring</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <!-- ==================== FEATURES / BENTO ==================== -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-label reveal">Mengapa SIS-OCMS</div>
            <h2 class="section-heading reveal">
                Setiap Komponen,<br>Terlacak Sempurna.
            </h2>
            <p class="section-description reveal">
                Dari penerimaan hingga distribusi kembali — setiap tahapan overhaul termonitor secara digital 
                dengan quality gate dan inventory control terintegrasi.
            </p>

            <div class="bento-grid">
                <!-- Card 1: Large -->
                <div class="bento-card large reveal skew-scroll">
                    <div class="bento-icon gold">📊</div>
                    <h3 class="bento-title">Real-time Dashboard Analytics</h3>
                    <p class="bento-text">
                        Pantau seluruh KPI operasional overhaul dari satu layar: jumlah komponen on-progress, 
                        rata-rata lead time, distribusi per tahapan, dan permintaan suku cadang yang tertunda. 
                        Semua data terupdate secara real-time.
                    </p>
                    <span class="bento-tag gold">Executive Dashboard</span>
                    <!-- Mini chart visual -->
                    <div class="bento-visual">
                        <div class="bento-bar" style="height: 32px; background: var(--accent-gold);"></div>
                        <div class="bento-bar" style="height: 48px; background: var(--accent-gold);"></div>
                        <div class="bento-bar" style="height: 24px; background: var(--accent-gold); opacity: 0.3;"></div>
                        <div class="bento-bar" style="height: 56px; background: var(--accent-gold);"></div>
                        <div class="bento-bar" style="height: 40px; background: var(--accent-gold);"></div>
                        <div class="bento-bar" style="height: 28px; background: var(--accent-gold); opacity: 0.3;"></div>
                        <div class="bento-bar" style="height: 64px; background: var(--accent-gold);"></div>
                        <div class="bento-bar" style="height: 36px; background: var(--accent-gold);"></div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bento-card reveal skew-scroll">
                    <div class="bento-icon cyan">🔒</div>
                    <h3 class="bento-title">Quality Gate Enforcement</h3>
                    <p class="bento-text">
                        Sistem memvalidasi kelengkapan checksheet dan approval Management sebelum
                        komponen boleh melanjutkan ke tahap berikutnya. Tidak ada lagi bypass manual.
                    </p>
                    <span class="bento-tag cyan">Zero Defect</span>
                </div>

                <!-- Card 3 -->
                <div class="bento-card reveal skew-scroll">
                    <div class="bento-icon green">📦</div>
                    <h3 class="bento-title">Smart Inventory Control</h3>
                    <p class="bento-text">
                        Setiap keputusan "Replace" pada inspeksi langsung memicu permintaan suku cadang ke gudang 
                        secara otomatis. Planner mendapat notifikasi real-time.
                    </p>
                    <span class="bento-tag green">Auto-Trigger</span>
                </div>

                <!-- Card 4 -->
                <div class="bento-card reveal skew-scroll">
                    <div class="bento-icon cyan">📱</div>
                    <h3 class="bento-title">QR Code Tracking</h3>
                    <p class="bento-text">
                        Setiap komponen memiliki QR unik. Mekanik cukup scan dengan HP untuk membuka data overhaul secara instan 
                        — langsung dari lapangan tanpa perlu akses desktop.
                    </p>
                    <span class="bento-tag cyan">Field-Ready</span>
                </div>

                <!-- Card 5: Large -->
                <div class="bento-card large reveal skew-scroll">
                    <div class="bento-icon gold">🖨️</div>
                    <h3 class="bento-title">Digital Berita Acara (BAST)</h3>
                    <p class="bento-text">
                        Generate Berita Acara Serah Terima secara otomatis dalam format PDF profesional — lengkap dengan 
                        timeline pengerjaan, hasil inspeksi, dan kolom tanda tangan tiga pihak. 100% paperless.
                    </p>
                    <span class="bento-tag gold">Automated PDF</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== WORKFLOW / TIMELINE ==================== -->
    <section class="workflow-section" id="workflow">
        <div class="container">
            <div style="text-align: center;">
                <div class="section-label reveal">8 Tahapan Overhaul</div>
                <h2 class="section-heading reveal" style="margin-left: auto; margin-right: auto;">
                    Alur Proses<br>
                    <span style="background: linear-gradient(135deg, var(--accent-gold), var(--accent-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">End-to-End</span>
                </h2>
                <p class="section-description reveal" style="margin-left: auto; margin-right: auto; text-align: center;">
                    Setiap komponen alat berat melewati 8 tahapan terstruktur — dari penerimaan hingga distribusi kembali. 
                    Semua terekam dan terlacak secara digital.
                </p>
            </div>

            <div class="timeline-wrapper">
                <div class="timeline-line"></div>
                <div class="timeline-line-glow" id="timelineGlow"></div>

                @php
                    $stages = [
                        ['num' => 1, 'name' => 'Receiving',      'desc' => 'Komponen diterima di PRC, dilakukan registrasi dan generate QR Code unik untuk identifikasi digital.'],
                        ['num' => 2, 'name' => 'DIS Assembling', 'desc' => 'Pembongkaran, pencucian & pengukuran via checksheet digital. Keputusan part: Reuse, Salvage/Repair, atau Replace.'],
                        ['num' => 3, 'name' => 'Machining & Fabrication', 'desc' => 'Part yang perlu perbaikan difabrikasi (FR otomatis). Part yang perlu diganti otomatis direquest ke gudang.'],
                        ['num' => 4, 'name' => 'Assembly',       'desc' => 'Perakitan ulang komponen dengan part yang telah lulus inspeksi, dipandu checksheet assembly per EGI.'],
                        ['num' => 5, 'name' => 'Test Performance & Painting', 'desc' => 'Uji fungsi di test bench dengan checksheet digital, dilanjutkan pengecatan dan dokumentasi foto.'],
                        ['num' => 6, 'name' => 'Delivery',       'desc' => 'Serah terima komponen dengan checksheet delivery. Kelengkapan diverifikasi sebelum keluar workshop.'],
                        ['num' => 7, 'name' => 'RFU',            'desc' => 'Komponen dinyatakan Ready for Use. Berita Acara dapat dicetak dan komponen siap didistribusi.'],
                    ];
                @endphp

                @foreach($stages as $stage)
                <div class="timeline-item reveal skew-scroll">
                    <div class="timeline-content">
                        <div class="timeline-stage-num">Stage {{ str_pad($stage['num'], 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="timeline-stage-name">{{ $stage['name'] }}</div>
                        <div class="timeline-stage-desc">{{ $stage['desc'] }}</div>
                    </div>
                    <div class="timeline-node">{{ $stage['num'] }}</div>
                    <div style="width: calc(50% - 48px);"></div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ==================== CTA / LOGIN SECTION ==================== -->
    <section class="cta-section" id="access">
        <div class="cta-glow"></div>
        <div class="container">
            <div class="cta-content">
                <div class="section-label reveal">Akses Sistem</div>
                <h2 class="cta-headline reveal">
                    Masuk ke <span style="background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">SIS-OCMS</span>
                </h2>
                <p class="cta-subtitle reveal">
                    Akses terbatas untuk karyawan PT Saptaindra Sejati. 
                    Gunakan NIK dan password yang telah diberikan oleh Tim IT.
                </p>

                <div class="login-form-card reveal">
                    @if (session('status'))
                        <div style="background: var(--accent-cyan-dim); border: 1px solid rgba(72,202,228,0.2); color: var(--accent-cyan); padding: 12px; border-radius: 12px; font-size: 0.8rem; margin-bottom: 16px;">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="form-group">
                            <label for="nik" class="form-label">NIK / Username</label>
                            <input id="nik" class="form-input" type="text" name="nik" value="{{ old('nik') }}" required autocomplete="username" placeholder="Contoh: SA001">
                            @error('nik')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                            @error('password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-remember">
                            <input id="remember_me" type="checkbox" name="remember">
                            <label for="remember_me">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn-login">Masuk ke Sistem →</button>
                    </form>

                    <p class="form-footer-text">
                        Hubungi Tim IT untuk pembuatan akun baru.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <footer class="footer">
        <div class="container">
            <p>
                &copy; {{ date('Y') }} <a href="#">PT Saptaindra Sejati</a> — Adaro Group<br>
                SIS-OCMS v1.0 · Plant Rebuild Centre · Overhaul Component Management System
            </p>
        </div>
    </footer>

    <!-- ==================== THREE.JS ==================== -->
    <script>
    (function() {
        const canvas = document.getElementById('hero-canvas');
        if (!canvas) return;

        // Skip animasi 3D di device lemah / user yang minta reduced motion
        const lowEnd = (navigator.deviceMemory && navigator.deviceMemory < 4)
            || (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2)
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (lowEnd) { canvas.style.display = 'none'; return; }

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 100);
        camera.position.set(3, 1, 6);

        // === Engine-like wireframe: TorusKnot ===
        const knotGeo = new THREE.TorusKnotGeometry(1.5, 0.45, 128, 24, 2, 3);
        const knotMat = new THREE.MeshBasicMaterial({
            color: 0xD4AF37,
            wireframe: true,
            transparent: true,
            opacity: 0.06
        });
        const knot = new THREE.Mesh(knotGeo, knotMat);
        knot.position.set(3, 0, -2);
        scene.add(knot);

        // === Outer ring ===
        const ringGeo = new THREE.TorusGeometry(2.8, 0.015, 16, 200);
        const ringMat = new THREE.MeshBasicMaterial({ color: 0x48CAE4, transparent: true, opacity: 0.05 });
        const ring1 = new THREE.Mesh(ringGeo, ringMat);
        ring1.position.set(3, 0, -2);
        ring1.rotation.x = Math.PI / 2.5;
        scene.add(ring1);

        // === Inner ring ===
        const ring2Geo = new THREE.TorusGeometry(1.8, 0.01, 16, 150);
        const ring2Mat = new THREE.MeshBasicMaterial({ color: 0xD4AF37, transparent: true, opacity: 0.04 });
        const ring2 = new THREE.Mesh(ring2Geo, ring2Mat);
        ring2.position.set(3, 0, -2);
        ring2.rotation.x = Math.PI / 1.5;
        ring2.rotation.y = Math.PI / 4;
        scene.add(ring2);

        // === Floating particles ===
        const pCount = 120;
        const pGeo = new THREE.BufferGeometry();
        const pPos = new Float32Array(pCount * 3);
        for (let i = 0; i < pCount * 3; i++) {
            pPos[i] = (Math.random() - 0.5) * 25;
        }
        pGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
        const pMat = new THREE.PointsMaterial({ color: 0xD4AF37, size: 0.025, transparent: true, opacity: 0.35 });
        const particles = new THREE.Points(pGeo, pMat);
        scene.add(particles);

        // Mouse tracking
        let mouseX = 0, mouseY = 0, targetMX = 0, targetMY = 0;
        document.addEventListener('mousemove', (e) => {
            mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
            mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
        });

        function animate() {
            // Pause render saat tab tidak aktif (hemat CPU/baterai)
            if (document.hidden) return;

            requestAnimationFrame(animate);

            // Smooth lerp
            targetMX += (mouseX - targetMX) * 0.015;
            targetMY += (mouseY - targetMY) * 0.015;

            // Idle rotation + mouse parallax
            knot.rotation.x += 0.001;
            knot.rotation.y += 0.002;
            knot.rotation.x += targetMY * 0.008;
            knot.rotation.y += targetMX * 0.008;

            ring1.rotation.z += 0.0008;
            ring1.rotation.x = Math.PI / 2.5 + targetMY * 0.04;

            ring2.rotation.z -= 0.0006;
            ring2.rotation.y = Math.PI / 4 + targetMX * 0.03;

            particles.rotation.y += 0.0002;
            particles.rotation.x += 0.0001;

            renderer.render(scene, camera);
        }
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) animate();
        });
        animate();

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    })();
    </script>

    <!-- ==================== GSAP ANIMATIONS ==================== -->
    <script>
    gsap.registerPlugin(ScrollTrigger);

    // --- REVEAL animations ---
    gsap.utils.toArray('.reveal').forEach((el, i) => {
        gsap.to(el, {
            opacity: 1,
            y: 0,
            duration: 1,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 88%',
                toggleActions: 'play none none none'
            },
            delay: i < 6 ? i * 0.08 : 0  // Only stagger the first few (hero)
        });
    });

    // --- Navbar scroll effect ---
    window.addEventListener('scroll', () => {
        document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
    });

    // --- Timeline glow progress ---
    const timelineGlow = document.getElementById('timelineGlow');
    if (timelineGlow) {
        ScrollTrigger.create({
            trigger: '.timeline-wrapper',
            start: 'top 60%',
            end: 'bottom 40%',
            onUpdate: (self) => {
                timelineGlow.style.height = (self.progress * 100) + '%';
            }
        });
    }

    // --- Velocity Scroll Skew Effect ---
    let currentSkew = 0;
    let skewTarget = 0;

    ScrollTrigger.create({
        onUpdate: (self) => {
            skewTarget = self.getVelocity() / -800;
            skewTarget = gsap.utils.clamp(-3, 3, skewTarget);
        }
    });

    function updateSkew() {
        currentSkew += (skewTarget - currentSkew) * 0.08;
        if (Math.abs(currentSkew) < 0.001) currentSkew = 0;

        gsap.utils.toArray('.skew-scroll').forEach(el => {
            el.style.transform = `skewY(${currentSkew}deg)`;
        });

        skewTarget *= 0.95; // decay
        requestAnimationFrame(updateSkew);
    }
    updateSkew();

    // --- Floating glow orbs ---
    gsap.to('.glow-orb-gold', { x: 40, y: 30, duration: 10, repeat: -1, yoyo: true, ease: 'sine.inOut' });
    gsap.to('.glow-orb-cyan', { x: -30, y: -40, duration: 12, repeat: -1, yoyo: true, ease: 'sine.inOut' });

    // --- Smooth scroll for anchor links ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                gsap.to(window, {
                    scrollTo: { y: target, offsetY: 80 },
                    duration: 1.2,
                    ease: 'power3.inOut'
                });
            }
        });
    });
    </script>
    <script src="{{ asset('vendor/ScrollToPlugin.min.js') }}"></script>

</body>
</html>
