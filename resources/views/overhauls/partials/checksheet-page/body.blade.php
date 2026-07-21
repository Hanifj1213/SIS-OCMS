    <!-- Header -->
    <div class="cs-header">
        <a href="{{ route('components.show', $comp->comp_id) }}" class="cs-header-back">
            ← Kembali
        </a>
        <div class="cs-header-title">
            <h2>{{ $comp->major_category }} Checksheet</h2>
            <span>{{ $stageName }} — {{ $comp->serial_number }}</span>
        </div>
        <div class="cs-header-right">
            <div class="cs-toggle-group">
                <button class="cs-toggle-btn active" id="btnSlideView" onclick="toggleMode('slide')">🔴 Slide</button>
                <button class="cs-toggle-btn" id="btnDaftarView" onclick="toggleMode('daftar')">📋 Daftar</button>
            </div>
            <div class="cs-header-counter" id="counter">
                {{ count($checksheet->answers ?? []) }}/{{ count($checksheet->items) }}
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="cs-progress">
        <div class="cs-progress-bar">
            <div class="cs-progress-fill" id="progressFill" style="width: {{ $checksheet->progress }}%"></div>
        </div>
        <div class="cs-progress-text">
            <span id="progressGroup">—</span>
            <span id="progressPercent">{{ $checksheet->progress }}%</span>
        </div>
    </div>

    <!-- Slide Area -->
    <div class="cs-slide-area" id="slideModeContainer">
        <div class="cs-slide slide-active" id="slideContent">
            <!-- Filled by JS -->
        </div>
    </div>

    <!-- Daftar Area -->
    <div class="cs-daftar-area" id="daftarModeContainer" style="display: none;">
        <div class="cs-daftar-container" id="daftarContent">
            <!-- Filled by JS -->
        </div>
    </div>

    <!-- Image Lightbox -->
    <div class="cs-lightbox" id="lightbox" style="display: none;" onclick="closeLightbox()">
        <button class="cs-lightbox-close" onclick="closeLightbox()">×</button>
        <img id="lightboxImg" src="" alt="">
        <div class="cs-lightbox-label" id="lightboxLabel"></div>
    </div>

    <!-- Navigation (Slide only) -->
    <div class="cs-nav" id="slideNavContainer">
        <button class="cs-nav-btn" id="btnPrev" onclick="navigate(-1)" disabled>← Prev</button>
        <button class="cs-nav-btn" id="btnNext" onclick="navigate(1)">Next →</button>
    </div>

    <!-- Add Item Modal -->
    <div class="cs-modal-overlay" id="addModal" style="display: none;">
        <div class="cs-modal">
            <h3>+ Tambah Item Checksheet</h3>
            <label
                style="font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">Nama
                Item</label>
            <input type="text" id="newItemLabel" placeholder="Contoh: Bracket Custom XYZ">
            <label
                style="font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">Grup</label>
            <select id="newItemGroup">
                <option value="Custom Items">Custom Items</option>
                <option value="Right Side View">Right Side View</option>
                <option value="Left Side View">Left Side View</option>
                <option value="Rear Side View">Rear Side View</option>
            </select>
            <div class="cs-modal-actions">
                <button class="cs-nav-btn" onclick="closeAddModal()">Batal</button>
                <button class="cs-nav-btn finish" onclick="submitAddItem()">Tambahkan</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="cs-toast" id="toast">✓ Tersimpan</div>
