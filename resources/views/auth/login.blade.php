<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SIS-OCMS') }} — Login</title>

        <!-- Fonts: Inter (Premium Modern) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

        <!-- Three.js -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

        <!-- GSAP -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --bg-primary: #0B2B26;
                --bg-secondary: #091528;
                --accent-gold: #D4AF37;
                --accent-gold-hover: #EAA112;
                --accent-cyan: #48CAE4;
                --glass-bg: rgba(255, 255, 255, 0.04);
                --glass-border: rgba(255, 255, 255, 0.08);
                --glass-bg-hover: rgba(255, 255, 255, 0.08);
                --text-primary: rgba(255, 255, 255, 0.92);
                --text-secondary: rgba(255, 255, 255, 0.55);
                --text-muted: rgba(255, 255, 255, 0.30);
            }

            * { box-sizing: border-box; margin: 0; padding: 0; }

            body.login-body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(160deg, var(--bg-primary) 0%, var(--bg-secondary) 60%, #0d1f3c 100%);
                min-height: 100vh;
                overflow: hidden;
                position: relative;
                color: var(--text-primary);
            }

            /* Three.js Canvas */
            #three-canvas-login {
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                z-index: 0;
                pointer-events: none;
            }

            /* Ambient Glow Orbs */
            .glow-orb {
                position: fixed;
                border-radius: 50%;
                filter: blur(120px);
                opacity: 0.15;
                pointer-events: none;
                z-index: 0;
            }
            .glow-orb-1 {
                width: 500px; height: 500px;
                background: var(--accent-gold);
                top: -150px; right: -100px;
                opacity: 0.08;
            }
            .glow-orb-2 {
                width: 400px; height: 400px;
                background: var(--accent-cyan);
                bottom: -100px; left: -80px;
                opacity: 0.06;
            }

            /* Login Container */
            .login-container {
                position: relative;
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 2rem;
            }

            .login-card {
                width: 100%;
                max-width: 420px;
                background: var(--glass-bg);
                backdrop-filter: blur(40px) saturate(150%);
                -webkit-backdrop-filter: blur(40px) saturate(150%);
                border: 1px solid var(--glass-border);
                border-radius: 24px;
                padding: 48px 40px;
                box-shadow:
                    0 0 0 1px rgba(255, 255, 255, 0.03) inset,
                    0 32px 64px rgba(0, 0, 0, 0.4),
                    0 0 120px rgba(212, 175, 55, 0.03);
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            /* Brand */
            .brand-section { text-align: center; margin-bottom: 40px; }
            .brand-logo {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 56px; height: 56px;
                background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-hover));
                border-radius: 16px;
                margin-bottom: 16px;
                box-shadow: 0 8px 24px rgba(212, 175, 55, 0.2);
            }
            .brand-logo svg { width: 28px; height: 28px; fill: #0B2B26; }
            .brand-title {
                font-size: 1.75rem;
                font-weight: 800;
                letter-spacing: -0.03em;
                background: linear-gradient(135deg, var(--text-primary) 0%, var(--accent-gold) 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .brand-subtitle {
                font-size: 0.8rem;
                color: var(--text-secondary);
                margin-top: 4px;
                letter-spacing: 0.05em;
            }
            .brand-company {
                font-size: 0.65rem;
                color: var(--text-muted);
                margin-top: 8px;
                text-transform: uppercase;
                letter-spacing: 0.15em;
            }

            /* Form Inputs */
            .form-group { margin-bottom: 20px; }
            .form-label {
                display: block;
                font-size: 0.75rem;
                font-weight: 600;
                color: var(--text-secondary);
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }
            .form-input {
                width: 100%;
                padding: 14px 16px;
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 12px;
                color: var(--text-primary);
                font-family: 'JetBrains Mono', monospace;
                font-size: 0.9rem;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                outline: none;
            }
            .form-input::placeholder {
                color: var(--text-muted);
                font-family: 'Inter', sans-serif;
            }
            .form-input:focus {
                border-color: var(--accent-gold);
                box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.12), 0 0 24px rgba(212, 175, 55, 0.06);
                background: rgba(255, 255, 255, 0.06);
            }

            /* Checkbox */
            .remember-row {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 24px;
            }
            .remember-row input[type="checkbox"] {
                width: 16px; height: 16px;
                accent-color: var(--accent-gold);
                border-radius: 4px;
                cursor: pointer;
            }
            .remember-row label {
                font-size: 0.8rem;
                color: var(--text-secondary);
                cursor: pointer;
            }

            /* Submit Button */
            .btn-login {
                width: 100% !important;
                padding: 16px !important;
                background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%) !important;
                border: none !important;
                border-radius: 12px !important;
                color: #0B2B26 !important;
                font-family: 'Inter', sans-serif !important;
                font-size: 0.9rem !important;
                font-weight: 700 !important;
                letter-spacing: 0.02em;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 4px 16px rgba(212, 175, 55, 0.25);
                position: relative;
                overflow: hidden;
                display: block !important;
                margin-top: 8px;
            }
            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 32px rgba(212, 175, 55, 0.35);
            }
            .btn-login:active {
                transform: translateY(0);
            }
            .btn-login::after {
                content: '';
                position: absolute;
                top: 0; left: -100%;
                width: 100%; height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                transition: left 0.5s;
            }
            .btn-login:hover::after { left: 100%; }

            /* Footer */
            .login-footer {
                text-align: center;
                margin-top: 24px;
                font-size: 0.7rem;
                color: var(--text-muted);
                line-height: 1.6;
            }

            /* Error Messages */
            .error-msg {
                font-size: 0.75rem;
                color: #ff6b6b;
                margin-top: 6px;
            }

            /* Session Status */
            .session-status {
                background: rgba(72, 202, 228, 0.1);
                border: 1px solid rgba(72, 202, 228, 0.2);
                color: var(--accent-cyan);
                padding: 12px 16px;
                border-radius: 12px;
                font-size: 0.8rem;
                margin-bottom: 20px;
            }

            /* Particles/grid pattern */
            .grid-pattern {
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                z-index: 1;
                opacity: 0.03;
                background-image:
                    linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px);
                background-size: 60px 60px;
                pointer-events: none;
            }
        </style>
    </head>
    <body class="login-body">

        <!-- Ambient Glow -->
        <div class="glow-orb glow-orb-1"></div>
        <div class="glow-orb glow-orb-2"></div>
        <div class="grid-pattern"></div>

        <!-- Three.js Canvas -->
        <canvas id="three-canvas-login"></canvas>

        <!-- Login Card -->
        <div class="login-container">
            <div class="login-card" id="loginCard">

                <!-- Brand -->
                <div class="brand-section">
                    <div class="brand-logo">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h1 class="brand-title">SIS-OCMS</h1>
                    <p class="brand-subtitle">Overhaul Component Management System</p>
                    <p class="brand-company">PT Saptaindra Sejati — Adaro Group</p>
                </div>

                @if (session('status'))
                    <div class="session-status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="form-group">
                        <label for="nik" class="form-label">NIK / Username</label>
                        <input id="nik" class="form-input" type="text" name="nik" value="{{ old('nik') }}" required autofocus autocomplete="username" placeholder="Contoh: SA001">
                        @error('nik')
                            <p class="error-msg">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        @error('password')
                            <p class="error-msg">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="remember-row">
                        <input id="remember_me" type="checkbox" name="remember">
                        <label for="remember_me">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk ke Sistem
                    </button>
                </form>

                <p class="login-footer">
                    Akses terbatas hanya untuk karyawan PT Saptaindra Sejati.<br>
                    Hubungi Tim IT untuk pembuatan akun baru.
                </p>
            </div>
        </div>

        <script>
        // === THREE.JS: Floating Gear Wireframe ===
        (function() {
            const canvas = document.getElementById('three-canvas-login');
            const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 100);
            camera.position.z = 6;

            // Create gear-like torus knot
            const geometry = new THREE.TorusKnotGeometry(1.2, 0.35, 100, 16, 2, 3);
            const material = new THREE.MeshBasicMaterial({
                color: 0xD4AF37,
                wireframe: true,
                transparent: true,
                opacity: 0.08
            });
            const mesh = new THREE.Mesh(geometry, material);
            scene.add(mesh);

            // Secondary ring
            const ringGeo = new THREE.TorusGeometry(2.0, 0.02, 16, 100);
            const ringMat = new THREE.MeshBasicMaterial({
                color: 0x48CAE4,
                transparent: true,
                opacity: 0.06
            });
            const ring = new THREE.Mesh(ringGeo, ringMat);
            ring.rotation.x = Math.PI / 3;
            scene.add(ring);

            let mouseX = 0, mouseY = 0;
            let targetX = 0, targetY = 0;

            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
                mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
            });

            function animate() {
                requestAnimationFrame(animate);

                // Smooth lerp towards mouse
                targetX += (mouseX - targetX) * 0.02;
                targetY += (mouseY - targetY) * 0.02;

                mesh.rotation.x += 0.002;
                mesh.rotation.y += 0.003;
                mesh.rotation.x += targetY * 0.01;
                mesh.rotation.y += targetX * 0.01;

                ring.rotation.z += 0.001;
                ring.rotation.x = Math.PI / 3 + targetY * 0.05;

                renderer.render(scene, camera);
            }
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        })();

        // === GSAP: Login Card Entrance ===
        gsap.to('#loginCard', {
            opacity: 1,
            y: 0,
            scale: 1,
            duration: 1.2,
            ease: 'power4.out',
            delay: 0.3
        });

        // Stagger form elements
        gsap.from('.form-group, .remember-row, .btn-login, .login-footer', {
            opacity: 0,
            y: 20,
            stagger: 0.1,
            duration: 0.8,
            ease: 'power3.out',
            delay: 0.8
        });

        // Subtle glow orb animation
        gsap.to('.glow-orb-1', {
            x: 30, y: 20,
            duration: 8,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut'
        });
        gsap.to('.glow-orb-2', {
            x: -20, y: -30,
            duration: 10,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut'
        });
        </script>
    </body>
</html>
