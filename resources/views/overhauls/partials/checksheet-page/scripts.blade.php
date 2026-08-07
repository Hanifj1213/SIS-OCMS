    <script>
        (function () {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const COMP_ID = {{ $comp->comp_id }};
            const STAGE = {{ $stage }};

            let items = @json($checksheet->items);
            let answers = @json($checksheet->answers ?? (object) []);
            let currentIndex = 0;
            let canInteract = @json(auth()->user()->canOperateOverhaul());
            let currentMode = 'slide';
            let daftarFilter = 'all';

            function getStageTwoReferenceImages(source) {
                if (!source) return [];

                const root = '/images/inspection/d375-6/stage2/';
                const mainline = source.match(/^D375-6 EG MAINLINE\.pdf p\.(\d+)(?:-(\d+))?$/);
                if (mainline) {
                    const firstPage = Number(mainline[1]);
                    const lastPage = Number(mainline[2] || mainline[1]);
                    const images = [];

                    for (let page = firstPage; page <= lastPage; page++) {
                        images.push({
                            src: root + 'mainline-p' + String(page).padStart(2, '0') + '.jpg',
                            label: 'D375-6 EG MAINLINE - halaman ' + page,
                        });
                    }

                    return images;
                }

                if (source === 'D375-6 EG SUBASSY.pdf p.2' || source === 'D375-6 EG SUBASSY.pdf p.5') {
                    const page = source.endsWith('p.2') ? '02' : '05';
                    return [{ src: root + 'subassy-p' + page + '.jpg', label: 'D375-6 EG SUBASSY - halaman ' + Number(page) }];
                }

                if (source === 'piston 170.pdf p.1 / PISTON CHECKSHEET2.pdf p.1') {
                    return [
                        { src: root + 'piston170-p01.jpg', label: 'Piston, Piston Ring, Piston Pin - halaman 1' },
                        { src: root + 'piston-checksheet-p01.jpg', label: 'Piston Measuring Check Sheet - halaman 1' },
                    ];
                }

                if (source === 'piston 170.pdf p.1') {
                    return [{ src: root + 'piston170-p01.jpg', label: 'Piston, Piston Ring, Piston Pin - halaman 1' }];
                }

                if (source === 'PISTON CHECKSHEET2.pdf p.1' || source === 'PISTON CHECKSHEET2.pdf p.2') {
                    const page = source.endsWith('p.1') ? '01' : '02';
                    return [{ src: root + 'piston-checksheet-p' + page + '.jpg', label: 'Piston Measuring Check Sheet - halaman ' + Number(page) }];
                }

                return [];
            }

            // Stage 2 shows the full original SOP page(s); Stage 1 keeps the
            // existing EGI view-image mapping.
            function getRefImage(item) {
                if (!item.group || item.custom) return null;

                const stageTwoImages = getStageTwoReferenceImages(item.source);
                if (stageTwoImages.length > 0) {
                    return { images: stageTwoImages, label: item.group };
                }

                const knownEgis = ['d375-6','hd785-7','d155-6','wa800-3','gd825a-2','hd465-7r','pc1250-8','pc2000-8','hd1500-7'];
                let egi = "{{ strtolower(trim($comp->egi ?? 'd375-6')) }}";
                if (!knownEgis.includes(egi)) egi = 'd375-6';

                const majorCategory = "{{ $comp->major_category }}";
                const slug = majorCategory === 'Engine'
                    ? item.group.toLowerCase().replace(/ /g, '-')
                    : majorCategory.toLowerCase().replace(/\//g, '-').replace(/ /g, '-');

                return {
                    images: [{
                        src: '/images/inspection/' + egi + '/' + slug + '.png',
                        label: majorCategory === 'Engine' ? item.group : majorCategory + ' Reference',
                    }],
                    label: majorCategory === 'Engine' ? item.group : majorCategory + ' Reference',
                };
            }

            window.toggleMode = function (mode) {
                currentMode = mode;
                document.getElementById('btnSlideView').classList.toggle('active', mode === 'slide');
                document.getElementById('btnDaftarView').classList.toggle('active', mode === 'daftar');
                document.getElementById('slideModeContainer').style.display = mode === 'slide' ? 'flex' : 'none';
                document.getElementById('slideNavContainer').style.display = mode === 'slide' ? 'flex' : 'none';
                document.getElementById('daftarModeContainer').style.display = mode === 'daftar' ? 'block' : 'none';
                document.body.style.overflow = mode === 'daftar' ? 'auto' : 'hidden';
                render();
            };

            window.openLightbox = function(src, label) {
                document.getElementById('lightboxImg').src = src;
                document.getElementById('lightboxLabel').textContent = label;
                document.getElementById('lightbox').style.display = 'flex';
            };

            window.closeLightbox = function() {
                document.getElementById('lightbox').style.display = 'none';
            };

            // Find first unanswered
            for (let i = 0; i < items.length; i++) {
                if (!answers[items[i].id]) { currentIndex = i; break; }
            }

            function render() {
                const total = items.length;
                const answered = Object.keys(answers).length;

                const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
                document.getElementById('progressFill').style.width = pct + '%';
                document.getElementById('progressPercent').textContent = pct + '%';
                document.getElementById('counter').textContent = answered + '/' + total;

                if (currentMode === 'daftar') { renderDaftar(); return; }

                const slide = document.getElementById('slideContent');

                // Completion screen
                if (currentIndex >= total) {
                    const goodCount = Object.values(answers).filter(v => v === 'good').length;
                    const badCount = Object.values(answers).filter(v => v === 'bad').length;
                    const noneCount = Object.values(answers).filter(v => v === 'none').length;

                    document.getElementById('progressGroup').textContent = 'Selesai!';
                    slide.innerHTML = `
                    <div class="cs-complete">
                        <div class="cs-complete-icon">🎉</div>
                        <h2>Checksheet Selesai!</h2>
                        <p>Semua ${total} item telah diperiksa</p>
                        <div class="cs-complete-stats">
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--accent-green);">${goodCount}</div>
                                <div class="cs-stat-label">Good ✓</div>
                            </div>
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--accent-red);">${badCount}</div>
                                <div class="cs-stat-label">Bad ✗</div>
                            </div>
                            <div class="cs-stat">
                                <div class="cs-stat-value" style="color: var(--text-muted);">${noneCount}</div>
                                <div class="cs-stat-label">N/A —</div>
                            </div>
                        </div>
                        <a href="${'{{ route('components.show', $comp->comp_id) }}'}" class="cs-nav-btn finish" style="text-decoration:none; display:inline-block;">← Kembali ke Detail Komponen</a>
                    </div>
                `;
                    document.getElementById('btnNext').style.display = 'none';
                    document.getElementById('btnPrev').disabled = false;
                    return;
                }

                document.getElementById('btnNext').style.display = '';

                const item = items[currentIndex];
                const currentAnswer = answers[item.id] || null;
                document.getElementById('progressGroup').textContent = item.group || '—';

                // Get reference image for this item
                const refImg = getRefImage(item);
                let refHtml = '';
                if (refImg) {
                    const multiplePages = refImg.images.length > 1;
                    const pageWidth = multiplePages ? 'min(30vw, 220px)' : '420px';
                    const pageHeight = multiplePages ? '250px' : '300px';
                    const imageHtml = refImg.images.map(image => `
                        <div style="text-align:center; min-width:0;">
                            <img src="${image.src}" alt="${image.label}" class="cs-slide-ref-thumb" style="width:${pageWidth}; height:${pageHeight}; max-width:100%; object-fit:contain;" onclick="openLightbox('${image.src}', '${image.label}')" title="📷 ${image.label} — klik untuk perbesar" onerror="this.parentElement.style.display='none'">
                            <div class="cs-slide-ref-label">📷 ${image.label}</div>
                        </div>
                    `).join('');
                    refHtml = `<div class="cs-slide-ref">${imageHtml}</div>`;
                }

                slide.innerHTML = `
                ${refHtml}
                <div class="cs-group-label">${item.group || ''}</div>
                <div class="cs-item-number">#${String(currentIndex + 1).padStart(2, '0')}</div>
                <div class="cs-item-label">${item.label}</div>
                ${item.standard ? `<div style="font-size:0.78rem; color:var(--text-secondary); line-height:1.45; max-width:620px; margin:0 auto 8px;">${item.standard}</div>` : ''}
                <div class="cs-item-meta">${item.custom ? '⚡ Custom Item' : 'Item standar SOP'}${item.source ? ' · ' + item.source : ''}</div>
                <div class="cs-answers">
                    <button class="cs-answer-btn good ${currentAnswer === 'good' ? 'selected' : ''}" onclick="answer('good')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">✓</span>
                        <span class="cs-answer-text">Good</span>
                        <span class="cs-answer-key">1</span>
                    </button>
                    <button class="cs-answer-btn bad ${currentAnswer === 'bad' ? 'selected' : ''}" onclick="answer('bad')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">✗</span>
                        <span class="cs-answer-text">Bad</span>
                        <span class="cs-answer-key">2</span>
                    </button>
                    <button class="cs-answer-btn none ${currentAnswer === 'none' ? 'selected' : ''}" onclick="answer('none')" ${!canInteract ? 'disabled' : ''}>
                        <span class="cs-answer-icon">—</span>
                        <span class="cs-answer-text">N/A</span>
                        <span class="cs-answer-key">3</span>
                    </button>
                </div>
            `;

                // Nav buttons
                document.getElementById('btnPrev').disabled = currentIndex === 0;
            }

            function renderDaftar() {
                const container = document.getElementById('daftarContent');
                const groups = {};
                items.forEach((item, index) => {
                    const grp = item.group || 'Lainnya';
                    if (!groups[grp]) groups[grp] = [];
                    groups[grp].push({ item, index });
                });

                const allGroups = Object.keys(groups);
                let filterHtml = `<div class="cs-daftar-filter">`;
                filterHtml += `<button class="cs-filter-btn ${daftarFilter === 'all' ? 'active' : ''}" onclick="setDaftarFilter('all')">Semua</button>`;
                allGroups.forEach(g => {
                    const short = g.replace(' Side View', ' Side').replace('Custom Items', 'Custom');
                    filterHtml += `<button class="cs-filter-btn ${daftarFilter === g ? 'active' : ''}" onclick="setDaftarFilter('${g}')">${short}</button>`;
                });
                filterHtml += `</div>`;

                let html = filterHtml;
                for (const grp in groups) {
                    if (daftarFilter !== 'all' && daftarFilter !== grp) continue;
                    html += `<div class="cs-list-group"><div class="cs-list-group-title">${grp}</div>`;
                    groups[grp].forEach(({ item, index }) => {
                        const ans = answers[item.id] || null;
                        let badgeHtml = '';
                        if (ans === 'good') badgeHtml = `<span class="cs-list-badge good">✓ GOOD</span>`;
                        else if (ans === 'bad') badgeHtml = `<span class="cs-list-badge bad">✗ BAD</span>`;
                        else if (ans === 'none') badgeHtml = `<span class="cs-list-badge none">— N/A</span>`;
                        else badgeHtml = `<span class="cs-list-badge unanswered">belum</span>`;

                        let actionBtns = badgeHtml;
                        if (canInteract) {
                            actionBtns += `<button class="cs-list-icon-btn delete" onclick="removeSpecificItem('${item.id}')" title="Hapus Item">🗑️</button>`;
                            actionBtns += `<button class="cs-list-icon-btn add" onclick="openAddModal()" title="Tambah Item Baru">➕</button>`;
                        }

                        html += `<div class="cs-list-item">
                            <div class="cs-list-item-info">
                                <div class="cs-list-item-num">${index + 1}</div>
                                <div class="cs-list-item-label">${item.label}</div>
                            </div>
                            <div class="cs-list-actions">${actionBtns}</div>
                        </div>`;
                    });
                    html += `</div>`;
                }
                container.innerHTML = html;
            }

            window.setDaftarFilter = function (filter) {
                daftarFilter = filter;
                renderDaftar();
            };

            const saveQueue = [];
            let saveBusy = false;

            async function flushSaves() {
                if (saveBusy) return;
                saveBusy = true;
                while (saveQueue.length) {
                    const job = saveQueue.shift();
                    let saved = false;
                    for (let attempt = 0; attempt < 3 && !saved; attempt++) {
                        try {
                            const r = await fetch(`/components/${COMP_ID}/checksheet/${STAGE}/answer`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': CSRF,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ item_id: job.item_id, answer: job.answer })
                            });
                            const data = await r.json().catch(() => ({}));
                            if (!r.ok) {
                                throw new Error(data.message || data.error || ('HTTP ' + r.status));
                            }
                            showToast('✓ Tersimpan');
                            saved = true;
                        } catch (e) {
                            if (attempt < 2) {
                                await new Promise(res => setTimeout(res, 250 * (attempt + 1)));
                                continue;
                            }
                            showToast('⚠ Gagal: ' + (e.message || 'jaringan'));
                        }
                    }
                }
                saveBusy = false;
            }

            window.answer = function (val) {
                if (!canInteract) return;
                const item = items[currentIndex];
                answers[item.id] = val;

                const existing = saveQueue.findIndex(j => j.item_id === item.id);
                if (existing >= 0) saveQueue.splice(existing, 1);
                saveQueue.push({ item_id: item.id, answer: val });
                flushSaves();

                setTimeout(() => {
                    currentIndex++;
                    animateSlide('right');
                }, 350);
            };

            window.navigate = function (dir) {
                const newIndex = currentIndex + dir;
                if (newIndex < 0) return;
                if (newIndex > items.length) return;
                currentIndex = newIndex;
                animateSlide(dir > 0 ? 'right' : 'left');
            };

            function animateSlide(direction) {
                const slide = document.getElementById('slideContent');
                slide.classList.remove('slide-active', 'slide-enter-right', 'slide-enter-left');
                slide.classList.add(direction === 'right' ? 'slide-enter-right' : 'slide-enter-left');

                setTimeout(() => {
                    render();
                    slide.classList.remove('slide-enter-right', 'slide-enter-left');
                    void slide.offsetWidth; // force reflow
                    slide.classList.add('slide-active');
                }, 150);
            }

            window.removeSpecificItem = function (itemId) {
                if (!confirm('Hapus item ini dari checksheet?')) return;

                fetch(`/components/${COMP_ID}/checksheet/${STAGE}/remove-item`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ item_id: itemId })
                }).then(r => r.json()).then(data => {
                    const idx = items.findIndex(i => i.id === itemId);
                    if (idx > -1) items.splice(idx, 1);
                    delete answers[itemId];
                    if (currentIndex >= items.length) currentIndex = items.length - 1;
                    if (currentIndex < 0) currentIndex = 0;
                    render();
                    showToast('🗑️ Item dihapus');
                });
            };

            window.openAddModal = function () {
                document.getElementById('addModal').style.display = 'flex';
                document.getElementById('newItemLabel').focus();
            };

            window.closeAddModal = function () {
                document.getElementById('addModal').style.display = 'none';
                document.getElementById('newItemLabel').value = '';
            };

            window.submitAddItem = function () {
                const label = document.getElementById('newItemLabel').value.trim();
                const group = document.getElementById('newItemGroup').value;
                if (!label) return;

                fetch(`/components/${COMP_ID}/checksheet/${STAGE}/add-item`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ label, group })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        items.push(data.item);
                        closeAddModal();
                        currentIndex = items.length - 1;
                        render();
                        showToast('+ Item ditambahkan');
                    }
                });
            };

            function showToast(msg) {
                const toast = document.getElementById('toast');
                toast.textContent = msg;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 1800);
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', function (e) {
                if (document.getElementById('addModal').style.display === 'flex') return;
                if (e.key === '1') answer('good');
                else if (e.key === '2') answer('bad');
                else if (e.key === '3') answer('none');
                else if (e.key === 'ArrowLeft') navigate(-1);
                else if (e.key === 'ArrowRight') navigate(1);
            });

            // Touch swipe
            let touchStartX = 0;
            document.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; });
            document.addEventListener('touchend', e => {
                const diff = e.changedTouches[0].screenX - touchStartX;
                if (Math.abs(diff) > 60) {
                    navigate(diff < 0 ? 1 : -1);
                }
            });

            // Initial render
            render();
        })();
    </script>
