    {{-- Stage 5 (Test Performance & Painting): dokumentasi foto hasil pengecatan --}}
    @if($viewedStage == 5)
    @php $paintingImages = array_values($comp->painting_images ?? []); @endphp
    <div class="section" id="painting-panel">
        <div class="section-title fade-up">🎨 Painting — Dokumentasi Foto ({{ count($paintingImages) }})</div>
        <div class="glass-card fade-up">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                Unggah foto komponen setelah selesai pengecatan sebagai bukti dokumentasi tahap Painting.
            </p>

            @ocmsOperate
            <form action="{{ route('components.painting.upload', $comp->comp_id) }}" method="POST" enctype="multipart/form-data"
                  style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; padding: 12px; border: 1px dashed rgba(var(--ink), 0.15); border-radius: 10px;">
                @csrf
                <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple required style="font-size: 0.75rem; max-width: 320px;">
                <button type="submit" class="btn-primary" style="padding: 8px 16px; font-size: 0.8rem;">📤 Upload Foto</button>
                <span style="font-size: 0.7rem; color: var(--text-muted);">JPG/PNG/WebP, maks. 10 MB per foto, hingga 12 foto sekali unggah.</span>
            </form>
            @endocmsOperate

            @if(count($paintingImages) > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px;">
                @foreach($paintingImages as $idx => $img)
                <div style="border: 1px solid rgba(var(--ink), 0.08); border-radius: 12px; overflow: hidden; background: rgba(0,0,0,0.15);">
                    <a href="{{ asset($img['path']) }}" target="_blank" title="Buka ukuran penuh">
                        <img src="{{ asset($img['path']) }}" alt="Foto painting {{ $idx + 1 }}"
                             style="display: block; width: 100%; height: 170px; object-fit: cover;">
                    </a>
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 10px;">
                        <span style="font-size: 0.65rem; color: var(--text-muted);">
                            {{ $img['uploaded_at'] ?? '' }}{{ !empty($img['uploaded_by']) ? ' · ' . $img['uploaded_by'] : '' }}
                        </span>
                        @ocmsOperate
                        <form action="{{ route('components.painting.delete', $comp->comp_id) }}" method="POST" style="margin: 0;"
                              onsubmit="return confirm('Hapus foto ini?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="index" value="{{ $idx }}">
                            <button type="submit" class="btn-secondary" style="padding: 3px 8px; font-size: 0.65rem; color: #f87171;">🗑</button>
                        </form>
                        @endocmsOperate
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 16px;">
                Belum ada foto dokumentasi painting.
            </p>
            @endif
        </div>
    </div>
    @endif
