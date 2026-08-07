<script>
// Amount, total part, dan grand total dihitung di klien sebagai umpan balik
// cepat. Nilai final dihitung ulang dari qty x unit_price saat render PDF.
// Nol tidak ditampilkan, mengikuti form asli yang membiarkannya kosong.
(function () {
    const qty = document.getElementById('fr-qty');
    const unit = document.getElementById('fr-unit');
    const labour = document.getElementById('fr-labour');
    const amountCell = document.getElementById('fr-amount');
    const totalPart = document.getElementById('fr-total-part');
    const grand = document.getElementById('fr-grand');
    const fmt = new Intl.NumberFormat('id-ID');
    const show = v => (v > 0 ? fmt.format(v) : '');

    function recalc() {
        const amount = (parseFloat(qty.value) || 0) * (parseFloat(unit.value) || 0);
        amountCell.textContent = show(amount);
        totalPart.textContent = show(amount);
        grand.textContent = show(amount + (parseFloat(labour.value) || 0));
    }

    [qty, unit, labour].forEach(el => el && el.addEventListener('input', recalc));
    recalc();
})();

// ===== Objek gambar bebas: geser + ubah ukuran, seperti di Word =====
// Posisi & ukuran disimpan dalam PERSEN terhadap kanvasnya, bukan piksel,
// supaya tata letak di layar sama dengan hasil cetak PDF yang lebarnya beda.
(function () {
    const MIN_W = 5, MAX_W = 100;
    const IMG_DEFAULTS = [
        { x: 2, y: 3, w: 46 }, { x: 50, y: 3, w: 46 },
        { x: 2, y: 40, w: 46 }, { x: 50, y: 40, w: 46 },
        { x: 26, y: 70, w: 46 },
    ];

    const partCanvas = document.getElementById('fr-canvas');
    let drag = null;
    let nextIndex = partCanvas ? partCanvas.querySelectorAll('.fr-obj').length : 0;

    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

    // Simpan posisi objek ke input tersembunyi miliknya.
    function store(obj) {
        const x = parseFloat(obj.style.left).toFixed(2);
        const y = parseFloat(obj.style.top).toFixed(2);
        const w = parseFloat(obj.style.width).toFixed(2);

        if (obj.classList.contains('fr-sig-obj')) {
            const role = obj.dataset.role;
            const set = (k, v) => {
                const el = document.querySelector(`input[name="signature_layout[${role}][${k}]"]`);
                if (el) el.value = v;
            };
            set('x', x); set('y', y); set('w', w);
            return;
        }

        obj.querySelector('input[name$="[x]"]').value = x;
        obj.querySelector('input[name$="[y]"]').value = y;
        obj.querySelector('input[name$="[w]"]').value = w;
    }

    // Satu mesin drag/resize dipakai kanvas gambar part maupun tanda tangan.
    function bind(canvas) {
        canvas.addEventListener('pointerdown', function (e) {
            const obj = e.target.closest('.fr-obj');
            if (!obj || e.button !== 0) return;

            document.querySelectorAll('.fr-obj').forEach(o => o.classList.remove('fr-obj-active'));
            obj.classList.add('fr-obj-active');

            drag = {
                obj,
                canvas,
                mode: e.target.classList.contains('fr-obj-handle') ? 'resize' : 'move',
                startX: e.clientX,
                startY: e.clientY,
                rect: canvas.getBoundingClientRect(),
                origX: parseFloat(obj.style.left) || 0,
                origY: parseFloat(obj.style.top) || 0,
                origW: parseFloat(obj.style.width) || 40,
            };
            obj.setPointerCapture(e.pointerId);
            e.preventDefault();
        });

        canvas.addEventListener('pointermove', function (e) {
            if (!drag || drag.canvas !== canvas) return;
            const dx = ((e.clientX - drag.startX) / drag.rect.width) * 100;
            const dy = ((e.clientY - drag.startY) / drag.rect.height) * 100;

            if (drag.mode === 'move') {
                drag.obj.style.left = clamp(drag.origX + dx, -5, 98) + '%';
                drag.obj.style.top = clamp(drag.origY + dy, -5, 98) + '%';
            } else {
                drag.obj.style.width = clamp(drag.origW + dx, MIN_W, MAX_W) + '%';
            }
            store(drag.obj);
        });

        ['pointerup', 'pointercancel'].forEach(ev =>
            canvas.addEventListener(ev, () => { drag = null; })
        );

        // Klik kanan = hapus gambar
        canvas.addEventListener('contextmenu', function (e) {
            const obj = e.target.closest('.fr-obj');
            if (!obj) return;
            e.preventDefault();

            if (obj.classList.contains('fr-sig-obj')) {
                if (!confirm('Hapus tanda tangan ini?')) return;
                obj.style.display = 'none';
                obj.querySelector('img').src = '';
                // Tandai supaya server menghapus gambar yang tersimpan
                const role = obj.dataset.role;
                let flag = document.querySelector(`input[name="signatures[${role}][remove_image]"]`);
                if (!flag) {
                    flag = document.createElement('input');
                    flag.type = 'hidden';
                    flag.name = `signatures[${role}][remove_image]`;
                    obj.closest('td').appendChild(flag);
                }
                flag.value = '1';
                return;
            }

            if (!confirm('Hapus gambar ini?')) return;
            obj.remove();
        });
    }

    if (partCanvas) bind(partCanvas);
    document.querySelectorAll('.fr-sig-canvas').forEach(bind);

    // ===== Tambah gambar part: berkali-kali, tanpa menimpa yang sudah ada =====
    window.frAddImages = function (input) {
        const files = Array.from(input.files || []);
        if (!files.length || !partCanvas) return;

        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const i = nextIndex++;
                const d = IMG_DEFAULTS[i] || IMG_DEFAULTS[IMG_DEFAULTS.length - 1];

                const obj = document.createElement('div');
                obj.className = 'fr-obj';
                obj.dataset.index = i;
                obj.style.cssText = `left:${d.x}%;top:${d.y}%;width:${d.w}%;`;
                obj.innerHTML =
                    '<img draggable="false">' +
                    '<span class="fr-obj-handle fr-no-print" title="Tarik untuk mengubah ukuran"></span>' +
                    `<input type="hidden" name="images[${i}][data]">` +
                    `<input type="hidden" name="images[${i}][x]" value="${d.x}">` +
                    `<input type="hidden" name="images[${i}][y]" value="${d.y}">` +
                    `<input type="hidden" name="images[${i}][w]" value="${d.w}">`;

                obj.querySelector('img').src = e.target.result;
                // Gambar baru dikirim sebagai data URL agar satu tombol bisa
                // dipakai berulang tanpa mengganggu berkas yang sudah ada.
                obj.querySelector('input[name$="[data]"]').value = e.target.result;

                partCanvas.appendChild(obj);
            };
            reader.readAsDataURL(file);
        });

        input.value = '';   // siap dipakai lagi
    };

    // Tanda tangan: pasang gambar ke objek bebasnya
    window.frSigPick = function (input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const obj = document.querySelector(`.fr-sig-obj[data-role="${input.dataset.role}"]`);
    const reader = new FileReader();
        reader.onload = function (e) {
            obj.querySelector('img').src = e.target.result;
            obj.style.display = '';
            store(obj);
        };
        reader.readAsDataURL(file);
    };

    // Tata ulang gambar part ke komposisi bawaan
    window.frResetLayout = function () {
        if (!partCanvas) return;
        partCanvas.querySelectorAll('.fr-obj').forEach((obj, i) => {
            const d = IMG_DEFAULTS[i] || IMG_DEFAULTS[IMG_DEFAULTS.length - 1];
            obj.style.left = d.x + '%';
            obj.style.top = d.y + '%';
            obj.style.width = d.w + '%';
            store(obj);
        });
    };
})();

// ===== Toolbar anotasi: seleksi, garis, panah, dan konektor =====
// Anotasi digambar sebagai elemen SVG dengan koordinat 0..100 (persen kanvas),
// sama seperti posisi gambar, jadi hasil cetak PDF sama dengan layar.
// Daftar anotasi disinkronkan ke input hidden `annotations_json` (JSON) dan
// disimpan server ke kolom `annotations`. Klik kanan anotasi = hapus.
(function () {
    const canvas = document.getElementById('fr-canvas');
    const svg = document.getElementById('fr-anno-svg');
    const hidden = document.getElementById('fr-annotations-json');
    const initialData = document.getElementById('fr-annotations-data');
    if (!canvas || !svg || !hidden) return;

    const NS = 'http://www.w3.org/2000/svg';
    const colorInput = document.getElementById('fr-anno-color');
    const widthInput = document.getElementById('fr-anno-width');
    const toolButtons = document.querySelectorAll('.fr-tb-btn[data-tool]');

    let tool = 'select';
    let items = [];
    try {
        items = JSON.parse(initialData?.textContent || hidden.value || '[]') || [];
        items = items.map((a, index) => ({
            ...a,
            id: Number.isFinite(Number(a.id)) ? Number(a.id) : index + 1,
            type: a.type === 'double_arrow' ? 'double-arrow' : a.type,
        }));
    } catch (e) { items = []; }

    let drawing = null;   // anotasi yang sedang dibuat
    let transform = null; // anotasi yang sedang digeser / diubah titik ujungnya
    let editor = null;    // editor teks yang sedang aktif
    let selected = null;  // id anotasi terpilih
    let uid = items.reduce((m, a) => Math.max(m, (a.id | 0) + 1), 1);
    const LINE_TYPES = ['line', 'arrow', 'connector', 'double-arrow'];

    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
    const num = (v) => Math.round(v * 100) / 100;
    const currentWidth = () => clamp(parseFloat(widthInput.value) || 2, 1, 20);
    const byId = (id) => items.find(a => a.id === id);

    function sync() {
        hidden.value = items.length ? JSON.stringify(items) : '';
    }

    function point(e) {
        const r = svg.getBoundingClientRect();
        return {
            x: clamp(((e.clientX - r.left) / r.width) * 100, 0, 100),
            y: clamp(((e.clientY - r.top) / r.height) * 100, 0, 100),
        };
    }

    function setTool(next) {
        tool = next;
        toolButtons.forEach(b => b.classList.toggle('fr-tb-active', b.dataset.tool === next));
        svg.classList.toggle('fr-anno-draw', next !== 'select');
        svg.querySelectorAll('g.fr-anno').forEach(g =>
            g.classList.toggle('fr-anno-hit', next === 'select'));
        if (next !== 'select') selectAnno(null);
        if (next !== 'text') closeEditor(true);
    }

    // ----- render satu anotasi ke elemen SVG -----
    function bbox(a) {
        if (a.type === 'text') {
            const fs = a.font_size || a.size || (2.2 * (a.stroke || 2));
            return { x: a.x, y: a.y, w: Math.max(6, (a.text || '').length * fs * 0.62), h: fs * 1.25 };
        }
        if (a.type === 'brush' && a.points.length) {
            const xs = a.points.map(p => p.x), ys = a.points.map(p => p.y);
            const pad = (a.stroke || 2) / 2;
            return { x: Math.min(...xs) - pad, y: Math.min(...ys) - pad,
                     w: Math.max(...xs) - Math.min(...xs) + pad * 2,
                     h: Math.max(...ys) - Math.min(...ys) + pad * 2 };
        }
        const pad = (a.stroke || 2) / 2;
        return { x: Math.min(a.x1, a.x2) - pad, y: Math.min(a.y1, a.y2) - pad,
                 w: Math.abs(a.x2 - a.x1) + pad * 2, h: Math.abs(a.y2 - a.y1) + pad * 2 };
    }

    // Kepala panah berupa segitiga penuh yang ukurannya mengikuti panjang
    // garis, sehingga ujungnya tegas tetapi tidak menjadi terlalu gemuk.
    function arrowWingPoints(a, atStart) {
        const tip = atStart ? { x: a.x1, y: a.y1 } : { x: a.x2, y: a.y2 };
        const tail = atStart ? { x: a.x2, y: a.y2 } : { x: a.x1, y: a.y1 };
        const dx = tip.x - tail.x, dy = tip.y - tail.y;
        const len = Math.hypot(dx, dy) || 1;
        const ux = dx / len, uy = dy / len;
        const size = Math.min(len * 0.30, 5 + (a.stroke || 2) * 0.35);
        const spread = size * 0.62;
        const bx = tip.x - ux * size, by = tip.y - uy * size;
        return [
            { x: bx + (-uy) * spread, y: by + ux * spread },
            tip,
            { x: bx - (-uy) * spread, y: by - ux * spread },
        ];
    }

    function appendArrowWing(g, a, atStart, strokeW) {
        const wing = document.createElementNS(NS, 'polygon');
        wing.setAttribute('points', arrowWingPoints(a, atStart)
            .map(p => `${num(p.x)},${num(p.y)}`).join(' '));
        wing.setAttribute('fill', a.color || '#dc2626');
        wing.classList.add('fr-anno-core');
        g.appendChild(wing);
    }

    function render(a) {
        let g = svg.querySelector(`g[data-anno-id="${a.id}"]`);
        if (!g) {
            g = document.createElementNS(NS, 'g');
            g.classList.add('fr-anno');
            g.dataset.annoId = a.id;
            svg.appendChild(g);
        }
        g.textContent = '';
        g.classList.toggle('fr-anno-hit', tool === 'select');
        g.classList.toggle('fr-anno-sel', selected === a.id);

        const strokeW = (a.stroke || 2) * 0.35; // satuan viewBox (≈ piksel layar / 5)

        if (a.type === 'text') {
            const fs = a.font_size || a.size || (2.2 * (a.stroke || 2));
            const hit = document.createElementNS(NS, 'rect');
            hit.setAttribute('x', a.x); hit.setAttribute('y', a.y);
            hit.setAttribute('width', Math.max(6, (a.text || '').length * fs * 0.62));
            hit.setAttribute('height', fs * 1.25);
            hit.setAttribute('fill', 'transparent');
            hit.classList.add('fr-anno-text-hit');
            g.appendChild(hit);

            const t = document.createElementNS(NS, 'text');
            t.setAttribute('x', a.x); t.setAttribute('y', a.y);
            t.setAttribute('font-size', fs);
            t.setAttribute('fill', a.color || '#dc2626');
            t.classList.add('fr-anno-text', 'fr-anno-core');
            t.textContent = a.text || '';
            g.appendChild(t);
        } else if (a.type === 'brush') {
            const hit = document.createElementNS(NS, 'polyline');
            hit.setAttribute('points', a.points.map(pt => `${num(pt.x)},${num(pt.y)}`).join(' '));
            hit.classList.add('fr-anno-hit-target');
            g.appendChild(hit);

            const p = document.createElementNS(NS, 'polyline');
            p.setAttribute('points', a.points.map(pt => `${num(pt.x)},${num(pt.y)}`).join(' '));
            p.setAttribute('fill', 'none');
            p.setAttribute('stroke', a.color || '#dc2626');
            p.setAttribute('stroke-width', strokeW);
            p.setAttribute('stroke-linecap', 'round');
            p.setAttribute('stroke-linejoin', 'round');
            p.classList.add('fr-anno-core');
            g.appendChild(p);
        } else if (LINE_TYPES.includes(a.type)) {
            // Jalur transparan yang lebih lebar memudahkan memilih garis tipis.
            const hit = document.createElementNS(NS, 'line');
            hit.setAttribute('x1', a.x1); hit.setAttribute('y1', a.y1);
            hit.setAttribute('x2', a.x2); hit.setAttribute('y2', a.y2);
            hit.classList.add('fr-anno-hit-target');
            g.appendChild(hit);

            const l = document.createElementNS(NS, 'line');
            l.setAttribute('x1', a.x1); l.setAttribute('y1', a.y1);
            l.setAttribute('x2', a.x2); l.setAttribute('y2', a.y2);
            l.setAttribute('stroke', a.color || '#dc2626');
            l.setAttribute('stroke-width', strokeW);
            l.setAttribute('stroke-linecap', 'round');
            l.classList.add('fr-anno-core');
            g.appendChild(l);
            if (a.type === 'connector') {
                [[a.x1, a.y1], [a.x2, a.y2]].forEach(([cx, cy]) => {
                    const c = document.createElementNS(NS, 'circle');
                    c.setAttribute('cx', cx); c.setAttribute('cy', cy);
                    c.setAttribute('r', Math.max(0.5, strokeW * 0.9));
                    c.setAttribute('fill', a.color || '#dc2626');
                    c.classList.add('fr-anno-core');
                    g.appendChild(c);
                });
            }
            if (a.type === 'arrow' || a.type === 'double-arrow') {
                appendArrowWing(g, a, false, strokeW);
            }
            if (a.type === 'double-arrow') {
                appendArrowWing(g, a, true, strokeW);
            }
        }

        // Kotak seleksi (hanya layar; disembunyikan saat cetak via CSS)
        const b = bbox(a);
        const box = document.createElementNS(NS, 'rect');
        box.setAttribute('x', num(b.x - 0.6)); box.setAttribute('y', num(b.y - 0.6));
        box.setAttribute('width', num(b.w + 1.2)); box.setAttribute('height', num(b.h + 1.2));
        box.classList.add('fr-anno-box');
        g.appendChild(box);

        // Atribut alias dipertahankan untuk skrip lama/integrasi eksternal.
        // Target klik tetap berasal dari elemen SVG transparan di atas.
        g.dataset.annotationId = a.id;

        // Garis/panah bisa diubah panjang dan arah melalui dua titik ujung.
        if (LINE_TYPES.includes(a.type)) {
            [['start', a.x1, a.y1], ['end', a.x2, a.y2]].forEach(([side, x, y]) => {
                const h = document.createElementNS(NS, 'circle');
                h.setAttribute('cx', x); h.setAttribute('cy', y); h.setAttribute('r', 1.25);
                h.dataset.handle = side;
                h.classList.add('fr-anno-handle');
                g.appendChild(h);
            });
        }
    }

    function renderAll() {
        svg.querySelectorAll('g.fr-anno').forEach(g => g.remove());
        items.forEach(render);
    }

    function selectAnno(id) {
        selected = id;
        svg.querySelectorAll('g.fr-anno').forEach(g =>
            g.classList.toggle('fr-anno-sel', Number(g.dataset.annoId) === id));
        syncControls();
    }

    // Compatibility hook untuk integrasi editor lama; sekarang kontrol
    // warna/tebal disinkronkan langsung saat objek dipilih.
    function syncControls() {
        const a = selected === null ? null : byId(selected);
        if (!a) return;
        colorInput.value = a.color || '#dc2626';
        widthInput.value = a.stroke || 2;
    }

    function applyProps(a) {
        a.color = colorInput.value;
        a.stroke = currentWidth();
        render(a); sync();
    }

    // ----- Insert Text -----
    function closeEditor(commit) {
        if (!editor) return;
        const { a, input } = editor;
        editor = null;
        if (commit) {
            const text = input.value.trim();
            if (text === '') {
                items = items.filter(item => item.id !== a.id);
            } else {
                a.text = text;
            }
            renderAll();
            sync();
        }
        input.remove();
    }

    function openEditor(a) {
        closeEditor(true);
        const rect = svg.getBoundingClientRect();
        const fontSize = a.font_size || a.size || (2.2 * (a.stroke || 2));
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'fr-anno-editor fr-no-print';
        input.value = a.text || '';
        input.placeholder = 'Ketik teks…';
        input.style.left = `${a.x / 100 * rect.width}px`;
        input.style.top = `${a.y / 100 * rect.height}px`;
        input.style.fontSize = `${fontSize / 100 * rect.height}px`;
        input.style.color = a.color || '#dc2626';
        input.style.width = '150px';
        canvas.appendChild(input);
        editor = { a, input };
        input.focus();
        input.select();
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                closeEditor(true);
                setTool('select');
                selected = a.id;
                renderAll();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeEditor(false);
                items = items.filter(item => item.id !== a.id);
                renderAll();
                sync();
            }
        });
        input.addEventListener('blur', () => closeEditor(true));
    }

    // ----- interaksi pointer -----
    svg.addEventListener('pointerdown', function (e) {
        if (e.button !== 0) return;
        const p = point(e);

        if (tool === 'select') {
            const g = e.target.closest('g.fr-anno');
            if (!g) { selectAnno(null); return; }
            const a = byId(Number(g.dataset.annoId));
            if (!a) return;
            selectAnno(a.id);
            colorInput.value = a.color || '#dc2626';
            widthInput.value = a.stroke || 2;
            transform = {
                a,
                mode: e.target.dataset.handle
                    ? `resize-${e.target.dataset.handle}`
                    : 'move',
                start: p,
                orig: JSON.parse(JSON.stringify(a)),
            };
            svg.setPointerCapture(e.pointerId);
            e.preventDefault();
            return;
        }

        if (tool === 'text') {
            const a = {
                id: uid++,
                type: 'text',
                x: num(p.x),
                y: num(p.y),
                text: '',
                color: colorInput.value,
                stroke: currentWidth(),
                font_size: 5,
            };
            items.push(a);
            render(a);
            sync();
            openEditor(a);
            e.preventDefault();
            return;
        }

        if (!LINE_TYPES.includes(tool)) return;

        const a = {
            id: uid++, type: tool,
            x1: num(p.x), y1: num(p.y), x2: num(p.x), y2: num(p.y),
            color: colorInput.value, stroke: currentWidth(),
        };
        drawing = a;
        items.push(a);
        render(a);
        svg.setPointerCapture(e.pointerId);
        e.preventDefault();
    });

    svg.addEventListener('pointermove', function (e) {
        const p = point(e);
        if (drawing) {
            drawing.x2 = num(p.x); drawing.y2 = num(p.y);
            render(drawing);
            return;
        }
        if (transform) {
            const a = transform.a, o = transform.orig;
            if (transform.mode === 'resize-start') {
                a.x1 = num(p.x); a.y1 = num(p.y);
            } else if (transform.mode === 'resize-end') {
                a.x2 = num(p.x); a.y2 = num(p.y);
            } else {
                const bounds = bbox(o);
                const dx = clamp(p.x - transform.start.x, -bounds.x, 100 - bounds.x - bounds.w);
                const dy = clamp(p.y - transform.start.y, -bounds.y, 100 - bounds.y - bounds.h);
                if (a.type === 'text') {
                a.x = num(clamp(o.x + dx, 0, 100)); a.y = num(clamp(o.y + dy, 0, 100));
                } else if (a.type === 'brush') {
                    a.points = o.points.map(pt => ({ x: num(pt.x + dx), y: num(pt.y + dy) }));
                } else {
                    a.x1 = num(o.x1 + dx); a.y1 = num(o.y1 + dy);
                    a.x2 = num(o.x2 + dx); a.y2 = num(o.y2 + dy);
                }
            }
            render(a);
        }
    });

    ['pointerup', 'pointercancel'].forEach(ev =>
        svg.addEventListener(ev, function () {
            if (drawing) {
                // Buang garis tanpa panjang (klik tanpa geser)
                const d = drawing;
                const tiny = Math.hypot(d.x2 - d.x1, d.y2 - d.y1) < 0.8;
                if (tiny) items = items.filter(x => x.id !== d.id);
                drawing = null;
                setTool('select');
                selected = tiny ? null : d.id;
                renderAll(); sync();
            }
            if (transform) { transform = null; sync(); }
        })
    );

    // Klik kanan anotasi = hapus (selaras dengan gambar)
    svg.addEventListener('contextmenu', function (e) {
        const g = e.target.closest('g.fr-anno');
        if (!g) return;
        e.preventDefault();
        if (!confirm('Hapus anotasi ini?')) return;
        items = items.filter(x => x.id !== Number(g.dataset.annoId));
        if (selected === Number(g.dataset.annoId)) selected = null;
        renderAll(); sync();
    });

    // Delete/Backspace menghapus anotasi terpilih tanpa dialog.
    document.addEventListener('keydown', function (e) {
        if (selected === null || !['Delete', 'Backspace'].includes(e.key)) return;
        if (['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) return;
        items = items.filter(x => x.id !== selected);
        selected = null;
        renderAll(); sync();
        e.preventDefault();
    });

    // Kontrol warna & tebal: berlaku ke anotasi terpilih, atau default berikutnya
    colorInput.addEventListener('input', function () {
        const a = selected !== null ? byId(selected) : null;
        if (a && a.type !== 'text') applyProps(a);
        else if (a) { a.color = colorInput.value; render(a); sync(); }
    });
    widthInput.addEventListener('input', function () {
        const a = selected !== null ? byId(selected) : null;
        if (a) applyProps(a);
    });

    toolButtons.forEach(b => b.addEventListener('click', () => setTool(b.dataset.tool)));

    setTool('select');
    renderAll();
    sync();
})();
</script>
