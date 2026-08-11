<?php

namespace Tests\Feature;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\GsheetTemplate;
use App\Models\User;
use App\Services\ChecksheetGsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeveloperRoleTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $nik): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::create([
            'name' => $role . ' Test',
            'nik' => $nik,
            'password' => 'password',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeComponent(array $overrides = []): Component
    {
        return Component::create(array_merge([
            'serial_number' => 'DEV-TEST-001',
            'model_type' => 'HD785-7',
            'major_category' => 'TC/Transmission',
            'egi' => 'HD785-7',
            'unit_code' => 'DT-100',
            'site_district' => 'SIS ADMO',
            'status_ovh' => 'SCHEDULE',
            'current_stage' => 1,
            'status' => 'On Progress',
        ], $overrides));
    }

    public function test_developer_can_open_dev_panel_but_mechanic_cannot(): void
    {
        $developer = $this->makeUser('Developer', 'DEV-T01');
        $mechanic = $this->makeUser('Mechanic', 'ME-T01');

        $this->actingAs($developer)->get(route('dev.index'))->assertOk();
        $this->actingAs($developer)->get(route('dev.gsheet-templates.index'))->assertOk();
        $this->actingAs($developer)->get(route('dev.checksheet-templates.index'))->assertOk();

        $this->actingAs($mechanic)->get(route('dev.index'))->assertForbidden();
        $this->actingAs($mechanic)->get(route('dev.gsheet-templates.index'))->assertForbidden();
    }

    public function test_gsheet_template_added_from_ui_is_used_by_service(): void
    {
        $developer = $this->makeUser('Developer', 'DEV-T02');
        $component = $this->makeComponent();

        $spreadsheetId = '1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789abcdef';

        $this->actingAs($developer)
            ->post(route('dev.gsheet-templates.store'), [
                'kind' => 'disassembly',
                'major_category' => 'TC/Transmission',
                'egi' => 'hd785-7',
                'spreadsheet' => 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit#gid=0',
            ])
            ->assertRedirect(route('dev.gsheet-templates.index'));

        $this->assertDatabaseHas('gsheet_templates', [
            'kind' => 'disassembly',
            'major_category' => 'TC/Transmission',
            'egi' => 'HD785-7',
            'spreadsheet_id' => $spreadsheetId,
        ]);

        $service = app(ChecksheetGsheetService::class);
        $this->assertSame($spreadsheetId, $service->templateIdFor($component, 'disassembly'));
    }

    public function test_config_fallback_still_works_when_db_has_no_entry(): void
    {
        GsheetTemplate::query()->delete();

        $component = $this->makeComponent([
            'serial_number' => 'DEV-TEST-CFG',
            'major_category' => 'Engine',
            'egi' => 'WA800-3',
            'model_type' => 'WA800-3',
        ]);

        $service = app(ChecksheetGsheetService::class);
        $this->assertSame(
            config('checksheet_gsheets.disassembly_templates.Engine.WA800-3'),
            $service->templateIdFor($component, 'disassembly'),
        );
    }

    public function test_developer_can_duplicate_and_edit_checksheet_template(): void
    {
        $developer = $this->makeUser('Developer', 'DEV-T03');

        $source = ChecksheetTemplate::create([
            'major_category' => 'TC/Transmission',
            'egi_model' => 'HD785-7',
            'stage_number' => 1,
            'template_name' => 'TC Receiving',
            'items' => [
                ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Painting'],
                ['id' => 'RCV-002', 'group' => 'Umum', 'label' => 'Name plate'],
            ],
        ]);

        $response = $this->actingAs($developer)->post(route('dev.checksheet-templates.store'), [
            'major_category' => 'TC/Transmission',
            'egi_model' => 'HD1500-7',
            'stage_number' => 1,
            'template_name' => 'TC HD1500 Receiving',
            'copy_from' => $source->id,
        ]);

        $new = ChecksheetTemplate::query()
            ->where('egi_model', 'HD1500-7')
            ->where('stage_number', 1)
            ->first();

        $this->assertNotNull($new);
        $this->assertCount(2, $new->items);
        $response->assertRedirect(route('dev.checksheet-templates.edit', $new));

        // Edit item: ubah label, tambah item baru tanpa ID (harus di-generate)
        $this->actingAs($developer)->put(route('dev.checksheet-templates.update', $new), [
            'template_name' => 'TC HD1500 Receiving',
            'items' => [
                ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Painting (updated)'],
                ['id' => '', 'group' => '', 'label' => 'Item baru'],
            ],
        ])->assertRedirect(route('dev.checksheet-templates.edit', $new));

        $items = $new->fresh()->items;
        $this->assertCount(2, $items);
        $this->assertSame('Painting (updated)', $items[0]['label']);
        $this->assertSame('Item baru', $items[1]['label']);
        $this->assertNotSame('', $items[1]['id']);
        $this->assertSame('Umum', $items[1]['group']);
    }

    public function test_developer_can_edit_and_delete_component_but_mechanic_cannot(): void
    {
        $developer = $this->makeUser('Developer', 'DEV-T04');
        $mechanic = $this->makeUser('Mechanic', 'ME-T04');
        $component = $this->makeComponent();

        $this->actingAs($mechanic)
            ->get(route('components.edit', $component->comp_id))
            ->assertForbidden();

        $this->actingAs($developer)
            ->get(route('components.edit', $component->comp_id))
            ->assertOk();

        $this->actingAs($developer)->put(route('components.update', $component->comp_id), [
            'egi' => 'hd785-7',
            'unit_code' => 'dt-200',
            'unit_serial_no' => '80588',
            'site_district' => 'SIS ADMO',
            'major_category' => 'TC/Transmission',
            'serial_number' => 'DEV-TEST-001-EDIT',
            'status_ovh' => 'UNSCHEDULE',
            'core_category' => 'B',
            'gsheet_url' => 'https://docs.google.com/spreadsheets/d/1abc/edit',
        ])->assertRedirect(route('components.show', $component->comp_id));

        $fresh = $component->fresh();
        $this->assertSame('DEV-TEST-001-EDIT', $fresh->serial_number);
        $this->assertSame('HD785-7', $fresh->egi);
        $this->assertSame('DT-200', $fresh->unit_code);
        $this->assertSame('UNSCHEDULE', $fresh->status_ovh);
        $this->assertSame('https://docs.google.com/spreadsheets/d/1abc/edit', $fresh->gsheet_url);

        $this->actingAs($mechanic)
            ->delete(route('components.destroy', $component->comp_id))
            ->assertForbidden();

        $this->actingAs($developer)
            ->delete(route('components.destroy', $component->comp_id))
            ->assertRedirect(route('components.index'));

        $this->assertDatabaseMissing('components', ['comp_id' => $component->comp_id]);
    }
}
