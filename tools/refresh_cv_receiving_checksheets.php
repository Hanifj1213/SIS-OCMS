<?php

/**
 * Refresh Control Valve stage-1 checksheet items from template
 * for components that belum selesai (answers kosong / belum complete).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;

$templates = ChecksheetTemplate::query()
    ->where('major_category', 'Control Valve')
    ->where('stage_number', 1)
    ->whereNotNull('egi_model')
    ->get()
    ->keyBy(fn ($t) => strtoupper(trim((string) $t->egi_model)));

$updated = 0;
$skipped = 0;

Component::query()
    ->where('major_category', 'Control Valve')
    ->get()
    ->each(function (Component $comp) use ($templates, &$updated, &$skipped) {
        $egi = strtoupper(trim((string) $comp->egi));
        $template = $templates->get($egi);
        if (!$template || empty($template->items)) {
            echo "skip comp #{$comp->comp_id}: no template for {$egi}\n";
            $skipped++;
            return;
        }

        $cs = ComponentChecksheet::firstOrNew([
            'comp_id' => $comp->comp_id,
            'stage_number' => 1,
        ]);

        if ($cs->exists && $cs->completed_at) {
            echo "skip comp #{$comp->comp_id}: already completed\n";
            $skipped++;
            return;
        }

        // Refresh items; clear answers karena id/item berubah dari generic → SOP
        $cs->items = $template->items;
        $cs->answers = [];
        $cs->completed_at = null;
        $cs->save();
        $updated++;
        echo "updated comp #{$comp->comp_id} {$egi}: " . count($template->items) . " items\n";
    });

echo "done updated={$updated} skipped={$skipped}\n";
