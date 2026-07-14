<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Scan QR Code</h1>
            <p>Arahkan kamera ke QR Code komponen untuk akses data secara instan</p>
        </div>
    </div>

    <div class="glass-card fade-up" style="max-width: 560px; margin: 0 auto; text-align: center; padding: 40px;">

        <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto; border-radius: 16px; overflow: hidden;"></div>

        <div id="result" style="display: none; margin-top: 24px; padding: 20px; background: var(--accent-green-dim); border: 1px solid rgba(52,211,153,0.15); border-radius: 14px;">
            <p style="font-weight: 700; color: var(--accent-green); font-size: 1rem;">✅ QR Code Terdeteksi!</p>
            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">Mengarahkan ke halaman komponen...</p>
        </div>

        <a href="{{ route('dashboard') }}" class="btn-secondary" style="margin-top: 24px;">← Kembali ke Dashboard</a>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        function onScanSuccess(decodedText, decodedResult) {
            html5QrcodeScanner.clear();
            document.getElementById('result').style.display = 'block';
            window.location.href = decodedText;
        }
        function onScanFailure(error) {}

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>

</x-app-layout>
