<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentChecksheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComponentStageReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_stage_checksheet_can_be_reviewed_without_changing_active_stage(): void
    {
        Role::create(['name' => 'Mechanic', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Stage Reviewer',
            'nik' => 'REVIEW-001',
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        $component = Component::create([
            'serial_number' => 'REVIEW-COMP-001',
            'model_type' => 'D155-6',
            'major_category' => 'Engine',
            'egi' => 'D155-6',
            'current_stage' => 3,
            'status' => 'On Progress',
        ]);

        ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 1,
            'items' => [
                ['id' => 'RCV-001', 'group' => 'Left Side View', 'label' => 'Painting condition'],
            ],
            'answers' => ['RCV-001' => 'good'],
            'completed_at' => now(),
        ]);

        ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 3,
            'items' => [
                ['id' => 'MCH-001', 'group' => 'Machining', 'label' => 'Machining condition'],
            ],
            'answers' => [],
        ]);

        $response = $this->actingAs($user)->get(route('components.show', [
            'component' => $component->comp_id,
            'review_stage' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Checksheet — Receiving (Penerimaan DC)', false);
        $response->assertDontSee('Mode review:', false);
        $response->assertDontSee('Kembali ke Tahap Aktif', false);
        $response->assertSee('review_stage=1#checksheet-review', false);
        $response->assertSee('const STAGE = 1;', false);
        $response->assertSee('const CAN_INTERACT = false;', false);
        $response->assertDontSee('Ajukan Approval ke Tahap 4');

        $this->assertSame(3, $component->fresh()->current_stage);

        $activeResponse = $this->actingAs($user)->get(route('components.show', [
            'component' => $component->comp_id,
            'review_stage' => 3,
        ]));

        $activeResponse->assertOk();
        $activeResponse->assertSee('Checksheet — Machining &amp; Fabrication (Perbaikan)', false);
        $activeResponse->assertSee('const STAGE = 3;', false);
        $activeResponse->assertSee('const CAN_INTERACT = true;', false);
        $activeResponse->assertDontSee('Mode review:', false);
    }

    public function test_reached_stage_without_checksheet_is_reviewable_without_changing_active_stage(): void
    {
        $user = User::create([
            'name' => 'Stage Reviewer',
            'nik' => 'REVIEW-002',
            'password' => 'password',
        ]);

        $component = Component::create([
            'serial_number' => 'REVIEW-COMP-002',
            'model_type' => 'D155-6',
            'major_category' => 'Engine',
            'egi' => 'D155-6',
            'current_stage' => 3,
            'status' => 'On Progress',
        ]);

        $response = $this->actingAs($user)->get(route('components.show', [
            'component' => $component->comp_id,
            'review_stage' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('Tahap 2 — DIS Assembling', false);
        $response->assertDontSee('Mode review saja.', false);
        $response->assertDontSee('Kembali ke Tahap Aktif', false);
        $this->assertSame(3, $component->fresh()->current_stage);

        $futureResponse = $this->actingAs($user)->get(route('components.show', [
            'component' => $component->comp_id,
            'review_stage' => 4,
        ]));

        $futureResponse->assertOk();
        $futureResponse->assertDontSee('Mode review saja. Snapshot checksheet digital untuk tahap ini belum tersedia', false);
    }
}
