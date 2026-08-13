<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\StageMechanicLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StageCrewIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;

    private Component $component;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Mechanic', 'web');
        $this->mechanic = User::create([
            'name' => 'Crew Test',
            'nik' => 'CREW-'.random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');

        $this->component = Component::create([
            'serial_number' => 'CREW-'.random_int(1000, 9999),
            'major_category' => 'Engine',
            'model_type' => 'Engine',
            'current_stage' => 3,
            'status' => 'On Progress',
        ]);
    }

    public function test_tidak_bisa_ubah_crew_tahap_historis(): void
    {
        $historical = StageMechanicLog::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 2,
            'user_id' => $this->mechanic->id,
            'crew_count' => 1,
            'crew_names' => 'Budi',
            'clock_in' => now()->subDay(),
        ]);

        $this->actingAs($this->mechanic)
            ->delete(route('components.crew.remove', [
                $this->component->comp_id,
                $historical->id,
            ]))
            ->assertSessionHasErrors('crew');
    }

    public function test_tidak_bisa_tambah_crew_saat_menunggu_approval(): void
    {
        $this->component->update(['is_waiting_approval' => true]);

        $this->actingAs($this->mechanic)
            ->post(route('components.crew.add', $this->component->comp_id), [
                'name' => 'Andi',
            ])
            ->assertSessionHasErrors('crew');
    }
}
