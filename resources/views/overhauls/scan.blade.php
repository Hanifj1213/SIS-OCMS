<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Scan QR Code</h1>
            <p>Arahkan kamera ke QR Code komponen untuk akses data secara instan</p>
        </div>
    </div>

    <div class="glass-card fade-up scan-card">
        <div id="scan-alert" class="scan-alert" style="display:none;"></div>

        <div id="reader-wrap">
            <div id="reader"></div>
        </div>

        <div id="result" class="scan-result" style="display:none;">
            <p class="scan-result-title">✅ QR Code Terdeteksi!</p>
            <p class="scan-result-sub">Mengarahkan ke halaman komponen...</p>
        </div>

        <div class="scan-fallback">
            <p class="scan-fallback-label">Alternatif tanpa kamera</p>
            <form id="manual-form" class="scan-manual-form">
                <input type="number" id="manual-comp-id" class="form-input" min="1" placeholder="ID komponen (contoh: 30)" required>
                <button type="submit" class="btn-primary">Buka Komponen</button>
            </form>
            <label class="scan-upload-btn">
                📷 Upload foto QR
                <input type="file" id="qr-file" accept="image/*" capture="environment" hidden>
            </label>
        </div>

        <a href="{{ route('dashboard') }}" class="btn-secondary scan-back">← Kembali ke Dashboard</a>
    </div>

    <style>
        .scan-card {
            max-width: 560px;
            margin: 0 auto;
            text-align: center;
            padding: 32px 24px;
        }
        #reader-wrap { width: 100%; max-width: 400px; margin: 0 auto; }
        #reader {
            border-radius: 16px;
            overflow: hidden;
            min-height: 280px;
            background: rgba(var(--ink), 0.04);
            border: 1px solid var(--glass-border);
        }
        .scan-alert {
            text-align: left;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.82rem;
            line-height: 1.55;
            margin-bottom: 16px;
            background: var(--accent-gold-dim);
            border: 1px solid rgba(212, 175, 55, 0.25);
            color: var(--text-primary);
        }
        .scan-result {
            margin-top: 20px;
            padding: 18px;
            background: var(--accent-green-dim);
            border: 1px solid rgba(52,211,153,0.15);
            border-radius: 14px;
        }
        .scan-result-title { font-weight: 700; color: var(--accent-green); font-size: 1rem; }
        .scan-result-sub { font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px; }
        .scan-fallback {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--glass-border);
            text-align: left;
        }
        .scan-fallback-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 12px;
            text-align: center;
        }
        .scan-manual-form {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .scan-manual-form .form-input { flex: 1; min-width: 0; }
        .scan-manual-form .btn-primary { white-space: nowrap; padding: 12px 16px; }
        .scan-upload-btn {
            display: block;
            text-align: center;
            padding: 12px;
            border: 1px dashed var(--glass-border-light);
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--text-secondary);
            transition: all 0.2s;
        }
        .scan-upload-btn:hover { background: rgba(var(--ink), 0.04); color: var(--text-primary); }
        .scan-back { margin-top: 20px; display: inline-block; }
        @media (max-width: 480px) {
            .scan-manual-form { flex-direction: column; }
            .scan-manual-form .btn-primary { width: 100%; }
        }
    </style>

    <script src="{{ asset('vendor/html5-qrcode.min.js') }}"></script>
    <script>
    (function() {
        const showUrl = @json(url('/components'));
        const alertEl = document.getElementById('scan-alert');
        const resultEl = document.getElementById('result');
        const readerWrap = document.getElementById('reader-wrap');
        let scanner = null;
        let scanning = false;

        function showAlert(msg) {
            alertEl.textContent = msg;
            alertEl.style.display = 'block';
        }

        function resolveComponentUrl(decodedText) {
            const trimmed = (decodedText || '').trim();
            const match = trimmed.match(/\/components\/(\d+)/);
            if (match) return showUrl + '/' + match[1];
            if (/^\d+$/.test(trimmed)) return showUrl + '/' + trimmed;
            return trimmed;
        }

        function goToComponent(decodedText) {
            const target = resolveComponentUrl(decodedText);
            if (!target) return;
            resultEl.style.display = 'block';
            if (scanner && scanning) {
                scanner.stop().finally(function() {
                    window.location.href = target;
                });
            } else {
                window.location.href = target;
            }
        }

        document.getElementById('manual-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('manual-comp-id').value.trim();
            if (id) goToComponent(id);
        });

        document.getElementById('qr-file').addEventListener('change', async function(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            try {
                const temp = new Html5Qrcode('reader');
                const text = await temp.scanFile(file, true);
                goToComponent(text);
            } catch (err) {
                showAlert('QR tidak terbaca dari foto. Pastikan gambar jelas dan coba lagi.');
            }
        });

        if (typeof Html5Qrcode === 'undefined') {
            showAlert('Library scanner gagal dimuat. Gunakan input ID manual atau upload foto QR di bawah.');
            readerWrap.style.display = 'none';
            return;
        }

        const isSecure = window.isSecureContext;
        const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);

        if (!isSecure && !isLocalhost) {
            showAlert(
                'Kamera tidak tersedia via HTTP (IP ' + window.location.hostname + '). ' +
                'Browser HP memblokir kamera kecuali HTTPS. ' +
                'Gunakan "Upload foto QR" atau ketik ID komponen di bawah. ' +
                'Untuk kamera live: deploy dengan HTTPS atau set APP_URL ke alamat server yang benar.'
            );
            readerWrap.style.display = 'none';
            return;
        }

        scanner = new Html5Qrcode('reader');
        const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
        const cameraConfig = { facingMode: 'environment' };

        scanner.start(cameraConfig, config, goToComponent, function() {})
            .then(function() { scanning = true; })
            .catch(function() {
                return scanner.start({ facingMode: 'user' }, config, goToComponent, function() {});
            })
            .then(function() { scanning = true; })
            .catch(function(err) {
                showAlert(
                    'Kamera gagal dibuka: ' + (err.message || err) + '. ' +
                    'Izinkan akses kamera di browser, atau gunakan upload foto / input ID manual.'
                );
                readerWrap.style.display = 'none';
            });
    })();
    </script>

</x-app-layout>
