    {{-- Skrip panel FR & MOL (Tab toggle, auto-scan, upload MOL) --}}
    @if(in_array($viewedStage, [2, 3], true))
    <script>
    (function() {
        const compId = @json($comp->comp_id);
        const csrf = @json(csrf_token());
        const scanUrl = @json(route('components.fr.scan', $comp->comp_id));
        const molUploadUrl = @json(route('components.mol.upload-document', $comp->comp_id));
        const molDeleteUrl = @json(route('components.mol.delete-document', $comp->comp_id));

        // Tab Toggle Elements
        const tabFrBtn = document.getElementById('tab-fr-btn');
        const tabMolBtn = document.getElementById('tab-mol-btn');
        const tabFrContent = document.getElementById('tab-fr-content');
        const tabMolContent = document.getElementById('tab-mol-content');

        if (tabFrBtn && tabMolBtn) {
            tabFrBtn.addEventListener('click', function() {
                tabFrBtn.className = 'btn-primary';
                tabMolBtn.className = 'btn-secondary';
                tabFrContent.style.display = 'block';
                tabMolContent.style.display = 'none';
            });

            tabMolBtn.addEventListener('click', function() {
                tabMolBtn.className = 'btn-primary';
                tabFrBtn.className = 'btn-secondary';
                tabMolContent.style.display = 'block';
                tabFrContent.style.display = 'none';
            });
        }

        // Scan Button (Auto-Create FR & MOL)
        const scanBtn = document.getElementById('fr-scan-btn');
        const scanStatus = document.getElementById('fr-scan-status');
        const scanProfile = document.getElementById('fr-scan-profile');

        if (scanBtn) {
            scanBtn.addEventListener('click', async function() {
                scanBtn.disabled = true;
                scanStatus.textContent = 'Memindai spreadsheet… (bisa 1–2 menit, jangan tutup halaman)';
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 280000);
                    const res = await fetch(scanUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({}),
                        signal: controller.signal,
                    });
                    clearTimeout(timeoutId);
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Scan gagal');

                    if (scanProfile && data.scan_profile_label) {
                        scanProfile.textContent = 'Profil scan: ' + data.scan_profile_label;
                    }

                    let msg = '✅ ' + (data.message || 'Scan selesai.');
                    if (data.gsheet_error) {
                        msg += ` (GSheet: ${data.gsheet_error})`;
                    } else if (data.gsheet_warning) {
                        msg += ` (Peringatan: ${data.gsheet_warning})`;
                    } else if (data.gsheet_sheet) {
                        msg += ` — sheet: ${data.gsheet_sheet}`;
                    }
                    if ((data.skipped || []).length) {
                        msg += `, ${data.skipped.length} dilewati (sudah ada)`;
                    }
                    scanStatus.textContent = msg;

                    // Reload jika ada FR/PR baru yang berhasil dibuat
                    const createdFrCount = (data.created_fr || []).length;
                    const createdPrCount = (data.created_pr || []).length;
                    if (createdFrCount > 0 || createdPrCount > 0) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } catch (e) {
                    scanStatus.textContent = e.name === 'AbortError'
                        ? '⚠ Scan timeout — coba lagi (pastikan internet & webapp GSheet aktif).'
                        : ('⚠ ' + (e.message || 'Gagal scan'));
                } finally {
                    scanBtn.disabled = false;
                }
            });
        }

        // Upload Dokumen MOL AJAX
        const molUploadForm = document.getElementById('mol-upload-form');
        const molUploadStatus = document.getElementById('mol-upload-status');
        if (molUploadForm) {
            molUploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const fileInput = document.getElementById('mol-doc-input');
                if (!fileInput || !fileInput.files.length) {
                    if (molUploadStatus) molUploadStatus.textContent = '⚠ Pilih file terlebih dahulu.';
                    return;
                }

                const formData = new FormData(molUploadForm);
                if (molUploadStatus) molUploadStatus.textContent = 'Mengunggah dokumen…';

                try {
                    const res = await fetch(molUploadUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: formData,
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Upload gagal');

                    if (molUploadStatus) molUploadStatus.textContent = '✅ ' + data.message;
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    if (molUploadStatus) molUploadStatus.textContent = '⚠ ' + (err.message || 'Gagal upload');
                }
            });
        }

        // Delete Dokumen MOL AJAX
        const molDocDeleteBtn = document.getElementById('mol-doc-delete-btn');
        if (molDocDeleteBtn) {
            molDocDeleteBtn.addEventListener('click', async function() {
                if (!confirm('Hapus dokumen MOL ini?')) return;

                if (molUploadStatus) molUploadStatus.textContent = 'Menghapus dokumen…';

                try {
                    const res = await fetch(molDeleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Hapus gagal');

                    if (molUploadStatus) molUploadStatus.textContent = '✅ Dokumen dihapus.';
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    if (molUploadStatus) molUploadStatus.textContent = '⚠ ' + (err.message || 'Gagal hapus');
                }
            });
        }
    })();
    </script>
    @endif
