<?php

namespace Tests\Feature;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;
use App\Models\User;
use Database\Seeders\ChecksheetTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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

    public function test_hd785_engine_keeps_the_source_numbering_gap_and_final_items(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);

        $items = ChecksheetTemplate::query()
            ->where('major_category', 'Engine')
            ->where('egi_model', 'HD785-7')
            ->where('stage_number', 1)
            ->firstOrFail()
            ->items;

        $this->assertCount(80, $items);
        $this->assertNotContains('RCV-061', array_column($items, 'id'));
        $this->assertSame([
            ['id' => 'RCV-079', 'group' => 'Front Side View', 'label' => 'RFID Tag, Traceability mounting bolt'],
            ['id' => 'RCV-080', 'group' => 'Front Side View', 'label' => 'Masking condition'],
            ['id' => 'RCV-081', 'group' => 'Front Side View', 'label' => 'Check Mounting Bolt (Rear Bracket & Front Support) (Std Tightening Torque : 25 kgm)'],
        ], array_slice($items, -3));
    }

    public function test_d375_engine_stage_two_contains_all_source_checksheet_groups(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);

        $template = ChecksheetTemplate::query()
            ->where('major_category', 'Engine')
            ->where('egi_model', 'D375-6')
            ->where('stage_number', 2)
            ->firstOrFail();

        $items = $template->items;
        $groups = array_values(array_unique(array_column($items, 'group')));

        $this->assertCount(276, $items);
        $this->assertContains('Disassembly Check Sheet', $groups);
        $this->assertContains('Piston Pin Measuring and Polishing', $groups);
        $this->assertContains('Camshaft Process and Measurement', $groups);
        $this->assertContains('Crankshaft Disassembly and Measurement', $groups);
        $this->assertContains('Connecting Rod Salvaging and Inspection', $groups);
        $this->assertContains('Cylinder Block Measuring and Inspection', $groups);
        $this->assertContains('Front Damper Inspection', $groups);
        $this->assertContains('Cylinder Head Before Machining and Measurement', $groups);
        $this->assertSame('Engine ass\'y', $items[0]['label']);
        $this->assertSame('Cylinder Block', $items[32]['label']);
        $this->assertSame('D375-6 EG MAINLINE.pdf p.6', $items[0]['source']);
        $this->assertSame('D375-6 EG SUBASSY.pdf p.5', $items[array_key_last($items) - 1]['source']);
    }

    public function test_d375_stage_two_snapshot_is_created_when_stage_one_advances(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);
        Role::create(['name' => 'Mechanic', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'D375 Mechanic',
            'nik' => 'D375-STAGE2-001',
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        $component = Component::create([
            'serial_number' => 'D375-STAGE2-001',
            'model_type' => 'D375-6',
            'major_category' => 'Engine',
            'egi' => 'D375-6',
            'current_stage' => 1,
            'status' => 'On Progress',
        ]);
        ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 1,
            'items' => [['id' => 'RCV-001', 'group' => 'Right Side View', 'label' => 'Painting condition']],
            'answers' => ['RCV-001' => 'good'],
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('components.updateStage', $component->comp_id));

        $response->assertRedirect(route('components.show', $component->comp_id));
        $this->assertSame(2, $component->fresh()->current_stage);
        $stage2 = ComponentChecksheet::query()
            ->where('comp_id', $component->comp_id)
            ->where('stage_number', 2)
            ->first();

        $this->assertNotNull($stage2);
        $this->assertGreaterThan(100, count($stage2->items));
        $this->assertSame('Engine ass\'y', $stage2->items[0]['label']);
    }

    public function test_stage_two_seed_merge_preserves_existing_answers_and_adds_new_source_items(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);
        $component = Component::create([
            'serial_number' => 'D375-STAGE2-MERGE',
            'model_type' => 'D375-6',
            'major_category' => 'Engine',
            'egi' => 'D375-6',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
        $sheet = ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 2,
            'items' => [['id' => 'DIS-001', 'group' => 'Disassembly Check Sheet', 'label' => 'Engine ass\'y']],
            'answers' => ['DIS-001' => 'good'],
        ]);

        $this->seed(ChecksheetTemplateSeeder::class);

        $fresh = $sheet->fresh();
        $this->assertSame(['DIS-001' => 'good'], $fresh->answers);
        $this->assertGreaterThan(100, count($fresh->items));
        $this->assertContains('DGT-001', array_column($fresh->items, 'id'));
    }

    public function test_d375_stage_two_screen_uses_source_groups_and_full_page_references(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);
        Role::create(['name' => 'Mechanic', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'D375 Stage Two Viewer',
            'nik' => 'D375-STAGE2-VIEW',
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        $template = ChecksheetTemplate::query()
            ->where('major_category', 'Engine')
            ->where('egi_model', 'D375-6')
            ->where('stage_number', 2)
            ->firstOrFail();
        $component = Component::create([
            'serial_number' => 'D375-STAGE2-VIEW',
            'model_type' => 'D375-6',
            'major_category' => 'Engine',
            'egi' => 'D375-6',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
        ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 2,
            'items' => $template->items,
            'answers' => [],
        ]);

        $response = $this->actingAs($user)->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee('id="csFilterButtons"', false);
        $response->assertSee('Piston Pin Measuring and Polishing', false);
        $response->assertSee('csGetStageTwoReferenceImages', false);
        $response->assertSee("/images/inspection/d375-6/stage2/", false);
        $response->assertSee("'mainline-p' + String(page).padStart(2, '0') + '.jpg'", false);
        $response->assertSee("'piston-checksheet-p' + page + '.jpg'", false);
    }
}
