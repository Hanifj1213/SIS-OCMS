    <!-- ==================== WORKFLOW / TIMELINE ==================== -->
    <section class="workflow-section" id="workflow">
        <div class="container">
            <div style="text-align: center;">
                <div class="section-label reveal">7 Tahapan Overhaul</div>
                <h2 class="section-heading reveal" style="margin-left: auto; margin-right: auto;">
                    Alur Proses<br>
                    <span style="background: linear-gradient(135deg, var(--accent-gold), var(--accent-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">End-to-End</span>
                </h2>
                <p class="section-description reveal" style="margin-left: auto; margin-right: auto; text-align: center;">
                    Setiap komponen alat berat melewati 7 tahapan terstruktur — dari penerimaan hingga RFU.
                    Semua terekam dan terlacak secara digital.
                </p>
            </div>

            <div class="timeline-wrapper">
                <div class="timeline-lines" aria-hidden="true">
                    <div class="timeline-line"></div>
                    <div class="timeline-line-glow" id="timelineGlow"></div>
                </div>
                <div class="timeline-track">

                @php
                    $stages = [
                        ['num' => 1, 'name' => 'Receiving',      'desc' => 'Komponen diterima di PRC, dilakukan registrasi dan generate QR Code unik untuk identifikasi digital.'],
                        ['num' => 2, 'name' => 'DIS Assembling', 'desc' => 'Pembongkaran, pencucian & pengukuran via checksheet digital. Keputusan part: Reuse, Salvage/Repair, atau Replace.'],
                        ['num' => 3, 'name' => 'Machining & Fabrication', 'desc' => 'Part yang perlu perbaikan difabrikasi (FR otomatis). Part yang perlu diganti otomatis direquest ke gudang.'],
                        ['num' => 4, 'name' => 'Assembly',       'desc' => 'Perakitan ulang komponen dengan part yang telah lulus inspeksi, dipandu checksheet assembly per EGI.'],
                        ['num' => 5, 'name' => 'Test Performance & Painting', 'desc' => 'Uji fungsi di test bench dengan checksheet digital, dilanjutkan pengecatan dan dokumentasi foto.'],
                        ['num' => 6, 'name' => 'Delivery',       'desc' => 'Serah terima komponen dengan checksheet delivery. Kelengkapan diverifikasi sebelum keluar workshop.'],
                        ['num' => 7, 'name' => 'RFU',            'desc' => 'Komponen dinyatakan Ready for Use. Berita Acara dapat dicetak dan komponen siap didistribusi.'],
                    ];
                @endphp

                @foreach($stages as $stage)
                <div class="timeline-item reveal skew-scroll">
                    <div class="timeline-node">{{ $stage['num'] }}</div>
                    <div class="timeline-content">
                        <div class="timeline-stage-num">Stage {{ str_pad($stage['num'], 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="timeline-stage-name">{{ $stage['name'] }}</div>
                        <div class="timeline-stage-desc">{{ $stage['desc'] }}</div>
                    </div>
                    <div class="timeline-spacer"></div>
                </div>
                @endforeach
                </div>
            </div>
        </div>
    </section>
