        <script>
        // === GSAP ScrollTrigger Animations ===
        gsap.registerPlugin(ScrollTrigger);

        // Animate all .fade-up elements
        gsap.utils.toArray('.fade-up').forEach((el, i) => {
            gsap.to(el, {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 90%',
                    toggleActions: 'play none none none'
                },
                delay: i * 0.05
            });
        });

        // === Toggle tema light/dark (tersimpan per-browser di localStorage) ===
        window.ocmsToggleTheme = function() {
            const html = document.documentElement;
            const next = html.dataset.theme === 'light' ? 'dark' : 'light';
            if (next === 'light') { html.dataset.theme = 'light'; } else { delete html.dataset.theme; }
            localStorage.setItem('ocms-theme', next);
        };

        // === Auto-dismiss alert sukses setelah 6 detik ===
        document.querySelectorAll('.alert-success').forEach(el => {
            setTimeout(() => {
                el.classList.add('alert-dismissing');
                setTimeout(() => el.remove(), 600);
            }, 6000);
        });
        </script>
