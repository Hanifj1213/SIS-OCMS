<?php

namespace Tests\Feature;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;
use Database\Seeders\ChecksheetTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivingChecksheetTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pc1250_control_valve_items_follow_the_source_image_order(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);

        $template = ChecksheetTemplate::query()
            ->where('major_category', 'Control Valve')
            ->where('egi_model', 'PC1250-8')
            ->where('stage_number', 1)
            ->firstOrFail();

        $this->assertSame([
            'Spool (5pcs)',
            'Main Relief Valve',
            'Suction Valve',
            'Suction Safety Valve (5pcs)',
            'Plug (4pcs)',
            'Jet Sensor Relief Valve',
            'Cover (5pcs)',
            'Name Plate',
            'Painting condition',
            'Packing / wraping',
            'Stand',
            'Bolt stand',
        ], array_column($template->items, 'label'));
    }

    public function test_all_model_specific_powertrain_templates_have_the_source_item_counts(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);

        $expectedCounts = [
            'TC/Transmission|HD785-7' => 35,
            'TC/Transmission|D155-6' => 28,
            'TC/Transmission|D375-6' => 35,
            'TC/Transmission|GD825A-2' => 9,
            'TC/Transmission|HD1500-7' => 29,
            'TC/Transmission|WA800-3' => 34,
            'Final Drive|HD785-7' => 19,
            'Final Drive|D155-6' => 18,
            'Final Drive|D375-6' => 25,
            'Final Drive|GD825A-2' => 16,
            'Final Drive|PC1250-8' => 24,
            'Final Drive|PC2000-8' => 22,
            'Differential|HD785-7' => 16,
            'PTO|PC1250-8' => 15,
            'PTO|PC2000-8' => 16,
            'Swing Machinery|PC1250-8' => 10,
            'Swing Machinery|PC2000-8' => 14,
            'Control Valve|PC1250-8' => 12,
            'Control Valve|PC2000-8' => 18,
        ];

        foreach ($expectedCounts as $key => $expectedCount) {
            [$category, $egi] = explode('|', $key);
            $template = ChecksheetTemplate::query()
                ->where('major_category', $category)
                ->where('egi_model', $egi)
                ->where('stage_number', 1)
                ->firstOrFail();

            $this->assertCount($expectedCount, $template->items, $key);
        }
    }

    public function test_seeder_refreshes_only_unanswered_receiving_snapshots(): void
    {
        $emptyComponent = Component::create([
            'serial_number' => 'TEST-CV-EMPTY',
            'model_type' => 'PC1250-8',
            'major_category' => 'Control Valve',
            'egi' => 'PC1250-8',
            'current_stage' => 1,
        ]);
        $emptySheet = ComponentChecksheet::create([
            'comp_id' => $emptyComponent->comp_id,
            'stage_number' => 1,
            'items' => [['id' => 'CVL-001', 'group' => 'Visual Inspection', 'label' => 'Old generic item']],
            'answers' => [],
        ]);

        $completedComponent = Component::create([
            'serial_number' => 'TEST-SWING-COMPLETE',
            'model_type' => 'PC1250-8',
            'major_category' => 'Swing Machinery',
            'egi' => 'PC1250-8',
            'current_stage' => 1,
        ]);
        $completedSheet = ComponentChecksheet::create([
            'comp_id' => $completedComponent->comp_id,
            'stage_number' => 1,
            'items' => [['id' => 'SWM-001', 'group' => 'Visual Inspection', 'label' => 'Historical item']],
            'answers' => ['SWM-001' => 'good'],
            'completed_at' => now(),
        ]);

        $this->seed(ChecksheetTemplateSeeder::class);

        $this->assertSame('Spool (5pcs)', $emptySheet->fresh()->items[0]['label']);
        $this->assertSame('Historical item', $completedSheet->fresh()->items[0]['label']);
        $this->assertSame(['SWM-001' => 'good'], $completedSheet->fresh()->answers);
    }

    public function test_engine_items_use_the_view_ranges_from_the_source_pages(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);

        $expectedRanges = [
            'D155-6' => [21 => 'Left Side View', 31 => 'Front Side View', 45 => 'Right Side View', 49 => 'Rear Side View'],
            'WA800-3' => [17 => 'Right Side View', 37 => 'Left Side View', 52 => 'Rear Side View', 56 => 'Front Side View'],
            'GD825A-2' => [20 => 'Left Side View', 37 => 'Front Side View', 57 => 'Right Side View', 69 => 'Rear Side View'],
            'HD465-7R' => [24 => 'Left Side View', 35 => 'Rear Side View', 49 => 'Right Side View', 61 => 'Front Side View'],
            'PC1250-8' => [20 => 'Right Side View', 40 => 'Rear Side View', 67 => 'Left Side View', 78 => 'Front Side View'],
            'PC2000-8' => [28 => 'Left Side View', 38 => 'Rear Side View', 57 => 'Right Side View', 76 => 'Front Side View'],
        ];

        foreach ($expectedRanges as $egi => $ranges) {
            $items = ChecksheetTemplate::query()
                ->where('major_category', 'Engine')
                ->where('egi_model', $egi)
                ->where('stage_number', 1)
                ->firstOrFail()
                ->items;

            foreach ($items as $item) {
                $itemNumber = (int) substr($item['id'], 4);
                $expectedView = null;

                foreach ($ranges as $lastItem => $view) {
                    if ($itemNumber <= $lastItem) {
                        $expectedView = $view;
                        break;
                    }
                }

                $this->assertSame($expectedView, $item['group'], "{$egi} item {$item['id']}");
            }
        }
    }
}
