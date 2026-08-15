<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Models\FrNumberSequence;
use App\Models\User;
use App\Services\FabricationRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FrNumberSequenceTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;

    private Component $component;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-15 10:00:00');

        Role::findOrCreate('Mechanic', 'web');
        $this->mechanic = User::create([
            'name' => 'Mekanik FR',
            'nik' => 'FR-SEQ-'.random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');

        $this->component = Component::create([
            'serial_number' => 'FRSEQ-'.random_int(1000, 9999),
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'major_category' => 'Engine',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function draftData(string $partName): array
    {
        return [
            'part_name' => $partName,
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'gsheet',
        ];
    }

    public function test_auto_number_berurutan_dalam_tahun_yang_sama(): void
    {
        $service = app(FabricationRequestService::class);

        $first = $service->createDraft($this->component, $this->draftData('PART A'), $this->mechanic->id);
        $second = $service->createDraft($this->component, $this->draftData('PART B'), $this->mechanic->id);

        $this->assertSame('FR/SIS/RC/0001/VIII/2026/INT', $first->fr_number);
        $this->assertSame('FR/SIS/RC/0002/VIII/2026/INT', $second->fr_number);
        $this->assertSame(2, FrNumberSequence::find(2026)?->last_number);
    }

    public function test_counter_reset_per_tahun(): void
    {
        $service = app(FabricationRequestService::class);

        $service->createDraft($this->component, $this->draftData('PART A'), $this->mechanic->id);

        Carbon::setTestNow('2027-01-05 09:00:00');
        $nextYear = $service->createDraft($this->component, $this->draftData('PART B'), $this->mechanic->id);

        $this->assertSame('FR/SIS/RC/0001/I/2027/INT', $nextYear->fr_number);
    }

    public function test_nomor_manual_duplikat_ditolak(): void
    {
        FabricationRequest::create([
            'comp_id' => $this->component->comp_id,
            'fr_number' => 'FR/SIS/RC/0099/VIII/2026/INT',
            'part_name' => 'EXISTING',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->mechanic)->post(
            route('components.fr.storeSingle', $this->component->comp_id),
            [
                'fr_number' => 'FR/SIS/RC/0099/VIII/2026/INT',
                'part_name' => 'PART BARU',
                'qty' => 1,
                'work_type' => 'repair',
            ],
        );

        $response->assertSessionHasErrors('fr_number');
        $this->assertSame(0, FabricationRequest::where('part_name', 'PART BARU')->count());
    }

    public function test_nomor_manual_tidak_mengubah_fr_lain_dari_scan(): void
    {
        $service = app(FabricationRequestService::class);

        $fromScan = $service->createDraft($this->component, $this->draftData('SHAFT A'), $this->mechanic->id);
        $fromScanNumber = $fromScan->fr_number;

        $manual = $service->createDraft(
            $this->component,
            $this->draftData('SHAFT B'),
            $this->mechanic->id,
            'FR/SIS/RC/0500/VIII/2026/INT',
        );

        $fromScan->refresh();
        $this->assertSame($fromScanNumber, $fromScan->fr_number);
        $this->assertSame('FR/SIS/RC/0500/VIII/2026/INT', $manual->fr_number);
    }

    public function test_edit_nomor_satu_fr_tidak_mengubah_fr_lain(): void
    {
        $service = app(FabricationRequestService::class);

        $frA = $service->createDraft($this->component, $this->draftData('PART A'), $this->mechanic->id);
        $frB = $service->createDraft($this->component, $this->draftData('PART B'), $this->mechanic->id);
        $numberB = $frB->fr_number;

        $this->actingAs($this->mechanic)->put(
            route('components.fr.update', [$this->component->comp_id, $frA->fr_id]),
            [
                'fr_number' => 'FR/SIS/RC/0888/VIII/2026/INT',
                'part_name' => 'PART A',
                'qty' => 1,
                'work_type' => 'repair',
            ],
        )->assertRedirect();

        $frA->refresh();
        $frB->refresh();

        $this->assertSame('FR/SIS/RC/0888/VIII/2026/INT', $frA->fr_number);
        $this->assertSame($numberB, $frB->fr_number);
    }

    public function test_nomor_manual_men_sync_counter_supaya_auto_tidak_tabrakan(): void
    {
        $service = app(FabricationRequestService::class);

        $service->createDraft(
            $this->component,
            $this->draftData('MANUAL HIGH'),
            $this->mechanic->id,
            'FR/SIS/RC/0100/VIII/2026/INT',
        );

        $service->syncSequenceFromManualNumber('FR/SIS/RC/0100/VIII/2026/INT');

        $nextAuto = $service->createDraft($this->component, $this->draftData('AUTO NEXT'), $this->mechanic->id);

        $this->assertSame('FR/SIS/RC/0101/VIII/2026/INT', $nextAuto->fr_number);
    }

    public function test_create_draft_manual_tidak_membuang_nomor_auto(): void
    {
        $service = app(FabricationRequestService::class);

        $service->createDraft(
            $this->component,
            $this->draftData('MANUAL ONLY'),
            $this->mechanic->id,
            'FR/SIS/RC/0200/VIII/2026/INT',
        );
        $service->syncSequenceFromManualNumber('FR/SIS/RC/0200/VIII/2026/INT');

        $auto = $service->createDraft($this->component, $this->draftData('AUTO AFTER MANUAL'), $this->mechanic->id);

        $this->assertSame('FR/SIS/RC/0201/VIII/2026/INT', $auto->fr_number);
        $this->assertSame(201, FrNumberSequence::find(2026)?->last_number);
    }
}
