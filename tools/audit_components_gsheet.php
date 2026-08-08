<?php
/**
 * Audit kelengkapan URL GSheet per komponen + scan smoke test stage 2.
 * Jalankan: php tools/audit_components_gsheet.php [--scan]
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$doScan = in_array('--scan', $argv ?? [], true);
$svc = new App\Services\ChecksheetGsheetService();
$components = App\Models\Component::orderBy('comp_id')->get();

echo '=== AUDIT KOMPONEN (' . $components->count() . " total)" . ($doScan ? ' + SCAN' : '') . " ===\n\n";

$issues = [];
$scanFails = [];

foreach ($components as $c) {
    $id = $c->comp_id;
    $label = "#{$id} {$c->egi} / {$c->major_category} SN:{$c->serial_number} stage:{$c->current_stage} status:{$c->status}";
    $rowIssues = [];

    $kinds = [
        'disassembly' => 'gsheet_url',
        'measurement' => 'gsheet_measurement_url',
        'subassy_disassembly' => 'gsheet_subassy_disassembly_url',
        'subassy_measurement' => 'gsheet_subassy_measurement_url',
        'assembly' => 'gsheet_assembly_url',
        'testbench' => 'gsheet_testbench_url',
        'sdr' => 'gsheet_sdr_url',
    ];

    foreach ($kinds as $kind => $col) {
        $tpl = $svc->templateIdFor($c, $kind);
        $val = $c->{$col};
        if ($tpl && !$val) {
            $rowIssues[] = "MISSING {$kind} (template ada)";
        }
        if (!$tpl && $val) {
            $rowIssues[] = "EXTRA {$kind} (tanpa template config)";
        }
    }

    if ((int) $c->current_stage >= 2) {
        if ($c->major_category === 'Engine') {
            if (!$c->gsheet_url && !$c->gsheet_subassy_disassembly_url
                && ($svc->templateIdFor($c, 'disassembly') || $svc->templateIdFor($c, 'subassy_disassembly'))) {
                $rowIssues[] = 'STAGE2+ tidak punya URL disassembly';
            }
        } else {
            if (!$c->gsheet_measurement_url && !$c->gsheet_subassy_measurement_url
                && $svc->templateIdFor($c, 'measurement')) {
                $rowIssues[] = 'STAGE2+ tidak punya URL measurement/inspection';
            }
        }
    }

    if ($svc->hasPendingDuplication($c)) {
        $rowIssues[] = 'PENDING duplikasi GSheet';
    }

    // Legacy PC2000-8 Engine tanpa gsheet_url tapi pakai fallback master
    if ($c->major_category === 'Engine' && strtoupper(trim((string) $c->egi)) === 'PC2000-8'
        && !$c->gsheet_url && ($c->gsheet_subassy_disassembly_url || (int) $c->current_stage >= 2)) {
        $rowIssues[] = 'PC2000-8 tanpa gsheet_url (masih fallback legacy master)';
    }

    if ($rowIssues) {
        $issues[$id] = [
            'label' => $label,
            'issues' => $rowIssues,
            'urls' => [
                'disassy' => $c->gsheet_url ?: '-',
                'measure' => $c->gsheet_measurement_url ?: '-',
                'sub_dis' => $c->gsheet_subassy_disassembly_url ?: '-',
                'sub_meas' => $c->gsheet_subassy_measurement_url ?: '-',
            ],
        ];
    }

    if ($doScan && (int) $c->current_stage >= 2) {
        $canScan = $c->major_category === 'Engine'
            ? ($c->gsheet_url || $c->gsheet_subassy_disassembly_url)
            : ($c->gsheet_measurement_url || $c->gsheet_subassy_measurement_url || $c->gsheet_url);

        if ($canScan) {
            try {
                $result = $svc->readPartDecisionRows($c);
                if (!empty($result['error'])) {
                    $scanFails[$id] = [
                        'label' => $label,
                        'error' => $result['error'],
                        'profile' => $result['profile'] ?? '?',
                    ];
                } else {
                    $n = count($result['rows'] ?? []);
                    echo "SCAN OK #{$id} ({$c->egi}): {$n} baris, profile=" . ($result['profile'] ?? '?') . "\n";
                }
            } catch (Throwable $e) {
                $scanFails[$id] = [
                    'label' => $label,
                    'error' => $e->getMessage(),
                    'profile' => '?',
                ];
            }
        }
    }
}

if (!$issues) {
    echo "URL/template: semua komponen OK (tidak ada missing/extra).\n\n";
} else {
    echo "--- MASALAH URL/TEMPLATE (" . count($issues) . ") ---\n\n";
    foreach ($issues as $id => $d) {
        echo $d['label'] . "\n";
        foreach ($d['issues'] as $i) {
            echo "  ! {$i}\n";
        }
        echo "  disassy: " . (strlen($d['urls']['disassy']) > 40 ? substr($d['urls']['disassy'], 0, 40) . '...' : $d['urls']['disassy']) . "\n";
        echo "  measure: " . (strlen($d['urls']['measure']) > 40 ? substr($d['urls']['measure'], 0, 40) . '...' : $d['urls']['measure']) . "\n";
        echo "  sub_dis: " . (strlen($d['urls']['sub_dis']) > 40 ? substr($d['urls']['sub_dis'], 0, 40) . '...' : $d['urls']['sub_dis']) . "\n";
        echo "  sub_meas: " . (strlen($d['urls']['sub_meas']) > 40 ? substr($d['urls']['sub_meas'], 0, 40) . '...' : $d['urls']['sub_meas']) . "\n\n";
    }
}

if ($doScan) {
    echo "\n--- SCAN GAGAL (" . count($scanFails) . ") ---\n\n";
    if (!$scanFails) {
        echo "Semua scan stage 2+ berhasil.\n";
    } else {
        foreach ($scanFails as $id => $d) {
            echo $d['label'] . "\n";
            echo "  ERROR: {$d['error']}\n\n";
        }
    }
} else {
    echo "Tip: jalankan dengan --scan untuk smoke test baca GSheet (butuh webapp + jaringan).\n";
}

echo "\nselesai\n";
