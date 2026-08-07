    {{-- Progress Bar --}}
    <div class="section">
        <div class="section-title fade-up">Progress Overhaul</div>
        <div class="glass-card fade-up" style="padding: 32px;">
            <div class="stage-bar">
                @for($i = 1; $i <= 7; $i++)
                    @php
                        $stageCanReview = $i <= $comp->current_stage;
                        $stageNodeClass = $i < $comp->current_stage ? 'completed' : ($i == $comp->current_stage ? 'active' : 'pending');
                        if ($reviewStage === $i) $stageNodeClass .= ' reviewing';
                        $stageHref = $i < $comp->current_stage
                            ? route('components.show', ['component' => $comp->comp_id, 'review_stage' => $i]) . '#checksheet-review'
                            : route('components.show', ['component' => $comp->comp_id]) . '#checksheet-review';
                    @endphp
                    @if($stageCanReview)
                        <a href="{{ $stageHref }}"
                           class="stage-node stage-review-link {{ $stageNodeClass }}"
                           title="Lihat {{ $stageNames[$i] ?? 'Tahap '.$i }}">
                            <div style="font-size: 1.1rem; font-weight: 800;">{{ $i }}</div>
                            <div style="font-size: 0.55rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em;">{{ explode(' (', $stageNames[$i] ?? '')[0] }}</div>
                        </a>
                    @else
                        <div class="stage-node {{ $stageNodeClass }}" title="Tahap ini belum aktif">
                            <div style="font-size: 1.1rem; font-weight: 800;">{{ $i }}</div>
                            <div style="font-size: 0.55rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em;">{{ explode(' (', $stageNames[$i] ?? '')[0] }}</div>
                        </div>
                    @endif
                    @if($i < 7)
                        <div class="stage-connector {{ $i < $comp->current_stage ? 'done' : 'undone' }}"></div>
                    @endif
                @endfor
            </div>
        </div>
    </div>
