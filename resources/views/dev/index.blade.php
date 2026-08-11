<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header">
            <h1>Panel Developer</h1>
            <p>Kelola master data sistem — tambah komponen/EGI baru tanpa mengubah kode</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fade-up">✅ {{ session('success') }}</div>
    @endif

    <div class="grid-2 fade-up">
        <a href="{{ route('dev.gsheet-templates.index') }}" class="glass-card module-card">
            <span class="module-icon">📊</span>
            <h3>Template Google Sheets</h3>
            <p>
                Mapping spreadsheet master per jenis (Disassembly, Measurement, Assembly, dst.)
                × kategori × EGI. Komponen baru otomatis mendapat salinan checksheet
                dari template yang terdaftar di sini.
            </p>
            <p style="margin-top: 12px;"><span class="badge badge-gold">{{ $gsheetCount }} mapping terdaftar</span></p>
            <div class="module-arrow">→</div>
        </a>

        <a href="{{ route('dev.checksheet-templates.index') }}" class="glass-card module-card">
            <span class="module-icon">📋</span>
            <h3>Template Checksheet Receiving / Delivery</h3>
            <p>
                Checksheet internal Stage 1 (Receiving) &amp; Stage 6 (Delivery).
                Duplikat dari EGI lain lalu sesuaikan item per item — tidak perlu seeder lagi.
            </p>
            <p style="margin-top: 12px;"><span class="badge badge-cyan">{{ $checksheetCount }} template terdaftar</span></p>
            <div class="module-arrow">→</div>
        </a>
    </div>

    <div class="section fade-up" style="margin-top: 32px;">
        <div class="glass-card" style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.7;">
            <strong style="color: var(--text-primary);">Cara menambah komponen powertrain baru:</strong>
            <ol style="padding-left: 20px; margin-top: 8px;">
                <li>Buat spreadsheet master di Google Drive (copy dari template SIAP), lalu daftarkan link-nya di <em>Template Google Sheets</em> untuk kind <em>disassembly</em> dan <em>measurement</em>.</li>
                <li>Buat template checksheet Receiving (stage 1) &amp; Delivery (stage 6) di <em>Template Checksheet</em> — paling cepat dengan duplikat dari EGI yang mirip.</li>
                <li>Daftarkan komponen seperti biasa — kategori/EGI baru otomatis muncul dan checksheet-nya tersalin sendiri.</li>
            </ol>
            <p style="margin-top: 10px; color: var(--text-muted);">
                Catatan: perubahan template checksheet hanya berlaku untuk komponen yang didaftarkan <strong>setelah</strong> perubahan — checksheet komponen lama adalah snapshot dan tidak berubah.
            </p>
        </div>
    </div>

</x-app-layout>
