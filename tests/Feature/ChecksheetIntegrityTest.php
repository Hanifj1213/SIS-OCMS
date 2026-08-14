<?php

namespace Tests\Feature;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * P2.6 — Integrity checksheet & snapshot historis.
 *
 * Aturan wajib di-enforce server-side; bypass UI review mode harus ditolak.
 */
class ChecksheetIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        Role::findOrCreate('Mechanic', 'web');

        $this->mechanic = User::create([
            'name' => 'Mekanik Integrity',
            'nik' => 'INT-MEC-'.random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');
    }

    private function makeComponent(int $currentStage, bool $waitingApproval = false): Component
    {
        $component = Component::create([
            'serial_number' => 'INT-'.random_int(10000, 99999),
            'egi' => 'SA12V140E-1',
            'model_type' => 'SA12V140E-1',
            'major_category' => 'Engine',
            'current_stage' => $currentStage,
            'is_waiting_approval' => $waitingApproval,
            'status' => 'On Progress',
        ]);

        $component->overhaulLogs()->create([
            'stage_number' => $currentStage,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now(),
        ]);

        return $component;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function makeChecksheet(Component $component, int $stage, array $items, array $answers = []): ComponentChecksheet
    {
        return ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => $stage,
            'items' => $items,
            'answers' => $answers,
            'completed_at' => null,
        ]);
    }

    public function test_mutasi_tahap_historis_ditolak_via_http(): void
    {
        $component = $this->makeComponent(3);
        $this->makeChecksheet($component, 1, [
            ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Item lama'],
        ]);
        $this->makeChecksheet($component, 3, [
            ['id' => 'MCH-001', 'group' => 'Umum', 'label' => 'Item aktif'],
        ]);

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$component->comp_id, 1]), [
                'item_id' => 'RCV-001',
                'answer' => 'good',
            ])
            ->assertForbidden()
            ->assertJson(['error' => 'Checksheet tahap historis/review tidak dapat diubah.']);

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.addItem', [$component->comp_id, 1]), [
                'label' => 'Custom lama',
            ])
            ->assertForbidden();

        $this->actingAs($this->mechanic)
            ->deleteJson(route('checksheet.removeItem', [$component->comp_id, 1]), [
                'item_id' => 'RCV-001',
            ])
            ->assertForbidden();
    }

    public function test_mutasi_ditolak_saat_menunggu_approval(): void
    {
        $component = $this->makeComponent(2, true);
        $this->makeChecksheet($component, 2, [
            ['id' => 'DIS-001', 'group' => 'Umum', 'label' => 'Item disassembly'],
        ]);

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$component->comp_id, 2]), [
                'item_id' => 'DIS-001',
                'answer' => 'good',
            ])
            ->assertForbidden()
            ->assertJson(['error' => 'Checksheet tidak dapat diubah saat menunggu approval.']);
    }

    public function test_mutasi_ditolak_setelah_checksheet_selesai(): void
    {
        $component = $this->makeComponent(1);
        ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 1,
            'items' => [['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Item']],
            'answers' => ['RCV-001' => 'good'],
            'completed_at' => now(),
        ]);

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$component->comp_id, 1]), [
                'item_id' => 'RCV-001',
                'answer' => 'bad',
            ])
            ->assertForbidden()
            ->assertJson(['error' => 'Checksheet yang sudah selesai tidak dapat diubah.']);
    }

    public function test_item_standar_tidak_bisa_dihapus(): void
    {
        $component = $this->makeComponent(1);
        $this->makeChecksheet($component, 1, [
            ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Standar'],
            ['id' => 'CUSTOM-ABC', 'group' => 'Custom', 'label' => 'Custom', 'custom' => true],
        ]);

        $this->actingAs($this->mechanic)
            ->deleteJson(route('checksheet.removeItem', [$component->comp_id, 1]), [
                'item_id' => 'RCV-001',
            ])
            ->assertUnprocessable()
            ->assertJson(['message' => 'Item standar/template tidak dapat dihapus.']);

        $this->actingAs($this->mechanic)
            ->deleteJson(route('checksheet.removeItem', [$component->comp_id, 1]), [
                'item_id' => 'CUSTOM-ABC',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_jawaban_orphan_tidak_membuat_progress_100(): void
    {
        $checksheet = ComponentChecksheet::create([
            'comp_id' => $this->makeComponent(1)->comp_id,
            'stage_number' => 1,
            'items' => [
                ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'A'],
                ['id' => 'RCV-002', 'group' => 'Umum', 'label' => 'B'],
            ],
            'answers' => [
                'RCV-001' => 'good',
                'ORPHAN-KEY' => 'good',
            ],
        ]);

        $this->assertSame(50, $checksheet->progress);
        $this->assertFalse($checksheet->is_complete);
        $this->assertNull($checksheet->completed_at);
    }

    public function test_save_answer_hanya_menandai_selesai_bila_semua_item_valid_terjawab(): void
    {
        $component = $this->makeComponent(1);
        $checksheet = $this->makeChecksheet($component, 1, [
            ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'A'],
            ['id' => 'RCV-002', 'group' => 'Umum', 'label' => 'B'],
        ]);

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$component->comp_id, 1]), [
                'item_id' => 'RCV-001',
                'answer' => 'good',
            ])
            ->assertOk();

        $checksheet->refresh();
        $this->assertNull($checksheet->completed_at);
        $this->assertSame(50, $checksheet->progress);

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$component->comp_id, 1]), [
                'item_id' => 'RCV-002',
                'answer' => 'bad',
            ])
            ->assertOk();

        $checksheet->refresh();
        $this->assertNotNull($checksheet->completed_at);
        $this->assertSame(100, $checksheet->progress);
    }

    public function test_save_spreadsheet_menolak_item_tidak_valid_dan_tahap_historis(): void
    {
        $component = $this->makeComponent(2);
        $this->makeChecksheet($component, 1, [
            ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Historis'],
        ]);
        $this->makeChecksheet($component, 2, [
            ['id' => 'DIS-001', 'group' => 'Umum', 'label' => 'Aktif'],
        ]);

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveSpreadsheet', [$component->comp_id, 1]), [
                'answers' => ['RCV-001' => 'good'],
            ])
            ->assertForbidden();

        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveSpreadsheet', [$component->comp_id, 2]), [
                'answers' => ['TIDAK-ADA' => 'good', 'DIS-001' => 'excellent'],
            ])
            ->assertUnprocessable();
    }

    public function test_perubahan_template_tidak_mengubah_snapshot_checksheet_komponen(): void
    {
        $component = $this->makeComponent(1);

        $template = ChecksheetTemplate::create([
            'major_category' => 'Engine',
            'egi_model' => 'SA12V140E-1',
            'stage_number' => 1,
            'template_name' => 'Receiving SA12',
            'items' => [
                ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Versi awal'],
            ],
        ]);

        $checksheet = ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 1,
            'items' => $template->items,
            'answers' => [],
        ]);

        $template->update([
            'items' => [
                ['id' => 'RCV-999', 'group' => 'Umum', 'label' => 'Versi baru'],
            ],
        ]);

        $checksheet->refresh();
        $this->assertSame('RCV-001', $checksheet->items[0]['id']);
        $this->assertSame('Versi awal', $checksheet->items[0]['label']);
    }
}
