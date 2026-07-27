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
