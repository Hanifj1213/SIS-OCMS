<?php
/**
 * Audit ONLINE checkbox kolom keputusan di master GSheet (read-only).
 * Sumber FR/PR: disassembly Engine, subassy disassembly Engine,
 * inspection Powertrain (measurement_templates non-Engine).
 * Checkbox Google Sheets terbaca sebagai boolean true/false lewat action read;
 * sel kosong tanpa checkbox terbaca sebagai string kosong.
 *
 * Pakai:  php tools/audit_gsheet_checkboxes.php [filter-substring-id/label]
 * Output: "TOKEN=NNcb" = ada NN checkbox; "TANPA-CHECKBOX" = header ada tapi
 *         belum di-Insert->Checkbox; "TIDAK ADA header keputusan" = kolom
 *         keputusan tidak ada di tab itu.
 */

$root = dirname(__DIR__);

// Baca .env manual (tanpa bootstrap Laravel biar cepat)
$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^([A-Z0-9_]+)=(.*)$/', trim($line), $m)) {
        $env[$m[1]] = trim($m[2], "\"' ");
    }
}
$url = $env['GSHEET_COPY_WEBAPP_URL'] ?? '';
$secret = $env['GSHEET_COPY_SECRET'] ?? '';
if (!$url) {
    fwrite(STDERR, "GSHEET_COPY_WEBAPP_URL tidak ada di .env\n");
    exit(1);
}

// Shim env() untuk config yang memanggil helper Laravel
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        global $env;
        return $env[$key] ?? $default;
    }
}

$config = require $root . '/config/checksheet_gsheets.php';

// Kumpulkan target unik: label => [id, tokens]
$targets = [];
$add = function (string $label, ?string $id, array $tokens) use (&$targets) {
    if ($id && !isset($targets[$id])) {
        $targets[$id] = ['label' => $label, 'tokens' => $tokens];
    }
};

$engineTokens = ['REUSE', 'SALVAGE', 'REPLACE'];
$inspTokens = ['U/A', 'U/R', 'R/N'];

foreach (($config['disassembly_templates']['Engine'] ?? []) as $egi => $id) {
    $add("Engine DISASSY $egi", $id, $engineTokens);
}
foreach (($config['subassy_disassembly_templates']['Engine'] ?? []) as $egi => $id) {
    $add("Engine SUBASSY DISASSY $egi", $id, $engineTokens);
}
foreach (($config['measurement_templates'] ?? []) as $cat => $byEgi) {
    if ($cat === 'Engine') continue; // Engine measurement bukan sumber FR
    foreach ($byEgi as $egi => $id) {
        $add("$cat INSPECTION $egi", $id, $inspTokens);
    }
}

function callRead(string $url, string $secret, string $id): array
{
    $body = json_encode(['action' => 'read', 'spreadsheet_id' => $id, 'secret' => $secret]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) {
        return ['ok' => false, 'error' => "curl: $err"];
    }
    $json = json_decode($res, true);
    return is_array($json) ? $json : ['ok' => false, 'error' => 'respons bukan JSON: ' . substr($res, 0, 120)];
}

$norm = fn ($v) => strtoupper(preg_replace('/\s+/', '', (string) $v));

// Filter opsional: argumen substring pada ID/label
if (!empty($argv[1])) {
    $targets = array_filter(
        $targets,
        fn ($t, $id) => stripos($id, $argv[1]) !== false || stripos($t['label'], $argv[1]) !== false,
        ARRAY_FILTER_USE_BOTH
    );
}

$i = 0;
$total = count($targets);
foreach ($targets as $id => $t) {
    $i++;
    echo "[$i/$total] {$t['label']} ($id)\n";
    $payload = callRead($url, $secret, $id);
    if (!($payload['ok'] ?? false)) {
        echo "    ERROR: " . ($payload['error'] ?? '?') . "\n";
        continue;
    }

    $sheets = $payload['sheets'] ?? [['name' => $payload['sheet'] ?? '?', 'values' => $payload['values'] ?? []]];
    foreach ($sheets as $sheet) {
        $values = $sheet['values'] ?? [];
        // Cari kolom keputusan: token di 40 baris pertama
        $cols = []; // token => colIndex
        foreach (array_slice($values, 0, 40) as $row) {
            foreach ($row as $ci => $cell) {
                $nv = $norm($cell);
                foreach ($t['tokens'] as $tok) {
                    $ntok = $norm($tok);
                    if ($nv === $ntok && !isset($cols[$tok])) {
                        $cols[$tok] = $ci;
                    }
                }
            }
        }

        if ($cols === []) {
            echo "    tab '{$sheet['name']}': TIDAK ADA header keputusan\n";
            continue;
        }

        $parts = [];
        foreach ($cols as $tok => $ci) {
            $bools = 0;
            foreach ($values as $row) {
                if (is_bool($row[$ci] ?? null)) $bools++;
            }
            $parts[] = "$tok=" . ($bools > 0 ? "{$bools}cb" : 'TANPA-CHECKBOX');
        }
        echo "    tab '{$sheet['name']}': " . implode(', ', $parts) . "\n";
    }
}
echo "SELESAI\n";
