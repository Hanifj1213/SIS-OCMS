<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\User;
use Database\Seeders\ChecksheetTemplateSeeder;
use Database\Seeders\DeliveryChecksheetTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Panel per-tahap untuk Stage 3-7:
 * - Stage 3: semua output FR ditampilkan langsung (PDF embed)
 * - Stage 4: checksheet Assembly dari GSheet (mirip Disassembly)
 * - Stage 5: checksheet Test Bench dari GSheet + dokumentasi foto Painting
 * - Stage 6: checksheet Delivery internal (mirip Receiving)
 * - Stage 7: panel penutup RFU (seluruh tahapan selesai)
 */
class StageFourToSevenPanelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // jangan panggil Apps Script sungguhan dari show()
    }

    private function mechanic(): User
    {
        Role::findOrCreate('Mechanic', 'web');
        $user = User::create([
            'name' => 'Mekanik Stage Panel',
            'nik' => 'STG-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        return $user;
    }

    /** PNG 1x1 piksel asli — GD (imagejpeg) tidak tersedia di lingkungan test. */
    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQAB'
            . 'h6FO1AAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    private function makeComponent(int $stage, array $attrs = []): Component
    {
        return Component::create(array_merge([
            'serial_number' => 'STG-' . random_int(10000, 99999),
            'egi' => 'SA12V140E-1',
            'model_type' => 'SA12V140E-1',
            'major_category' => 'Engine',
            'current_stage' => $stage,
            'status' => 'On Progress',
        ], $attrs));
    }

    // ===== Stage 3 =====

    public function test_stage_3_menampilkan_semua_output_fr(): void
    {
        $component = $this->makeComponent(3);
        $fr = $component->fabricationRequests()->create([
            'fr_number' => 'FR/SIS/RC/0001/VIII/2026/INT',
            'part_name' => 'CYLINDER LINER',
            'work_type' => 'repair',
            'source' => 'gsheet',
            'status' => 'draft',
            'requested_by' => null,
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee('id="fr-output-panel"', false);
        $response->assertSee('FR/SIS/RC/0001/VIII/2026/INT');
        $response->assertSee('class="fr-pdf-embed"', false);
        $response->assertSee(route('components.fr.pdf', [$component->comp_id, $fr->fr_id]), false);
    }

    public function test_panel_output_fr_tidak_muncul_di_stage_2(): void
    {
        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $this->makeComponent(2)->comp_id));

        $response->assertOk();
        $response->assertDontSee('id="fr-output-panel"', false);
    }

    // ===== Stage 4 =====

    public function test_stage_4_menampilkan_embed_gsheet_assembly(): void
    {
        $component = $this->makeComponent(4, [
            'gsheet_assembly_url' => 'https://docs.google.com/spreadsheets/d/ASSY123/edit',
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee('Assembly — Checksheet');
        $response->assertSee('id="gsheet-iframe-assembly"', false);
        $response->assertSee('https://docs.google.com/spreadsheets/d/ASSY123/edit?rm=minimal', false);
    }

    public function test_stage_4_tanpa_gsheet_tidak_menampilkan_embed_assembly(): void
    {
        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $this->makeComponent(4)->comp_id));

        $response->assertOk();
        $response->assertDontSee('id="gsheet-iframe-assembly"', false);
    }

    // ===== Stage 5 =====

    public function test_stage_5_dengan_gsheet_testbench_menampilkan_embed(): void
    {
        $component = $this->makeComponent(5, [
            'gsheet_testbench_url' => 'https://docs.google.com/spreadsheets/d/BENCH123/edit',
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee('Test Bench — Checksheet');
        $response->assertSee('id="gsheet-iframe-testbench"', false);
        $response->assertDontSee('name="oil_pressure"', false);

        // Ajukan approval: tidak ada lagi validasi Quality Gate manual
        $this->actingAs($this->mechanic())
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();

        $this->assertTrue($component->fresh()->is_waiting_approval);
    }

    public function test_stage_5_tidak_lagi_menampilkan_quality_gate_manual(): void
    {
        // Quality Gate tekanan oli dihapus total — komponen tanpa GSheet
        // test bench pun tidak lagi diminta input manual.
        $component = $this->makeComponent(5);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertDontSee('name="oil_pressure"', false);
        $response->assertDontSee('Quality Gate — Test Performance');

        $this->actingAs($this->mechanic())
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();

        $this->assertTrue($component->fresh()->is_waiting_approval);
    }

    // ===== Stage 5: Painting (digabung dengan Test Performance) =====

    public function test_stage_5_upload_dan_hapus_foto_painting(): void
    {
        Storage::fake('public');
        $component = $this->makeComponent(5);
        $user = $this->mechanic();

        $show = $this->actingAs($user)->get(route('components.show', $component->comp_id));
        $show->assertOk();
        $show->assertSee('id="painting-panel"', false);
        $show->assertSee('Belum ada foto dokumentasi painting.');

        // Upload dua foto
        $this->actingAs($user)
            ->post(route('components.painting.upload', $component->comp_id), [
                'photos' => [
                    $this->fakePng('cat1.png'),
                    $this->fakePng('cat2.png'),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $images = $component->fresh()->painting_images;
        $this->assertCount(2, $images);
        Storage::disk('public')->assertExists(preg_replace('#^storage/#', '', $images[0]['path']));

        // Hapus foto pertama
        $this->actingAs($user)
            ->delete(route('components.painting.delete', $component->comp_id), ['index' => 0])
            ->assertSessionHasNoErrors();

        $this->assertCount(1, $component->fresh()->painting_images);
    }

    public function test_panel_painting_tidak_muncul_di_stage_6(): void
    {
        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $this->makeComponent(6)->comp_id));

        $response->assertOk();
        $response->assertDontSee('id="painting-panel"', false);
    }

    // ===== Stage 6: Delivery =====

    public function test_stage_6_menampilkan_checksheet_delivery_dari_template(): void
    {
        $this->seed(DeliveryChecksheetTemplateSeeder::class);
        $component = $this->makeComponent(6);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        // Snapshot checksheet dibuat dari template Delivery SA12V140E-1
        $checksheet = $component->fresh()->checksheets->where('stage_number', 6)->first();
        $this->assertNotNull($checksheet);
        $this->assertCount(55, $checksheet->items);
        $response->assertSee('Flywheel housing');
        $response->assertSee('R.H. View');
    }

    public function test_stage_6_kategori_lain_memakai_clone_template_receiving(): void
    {
        $this->seed(ChecksheetTemplateSeeder::class);
        $this->seed(DeliveryChecksheetTemplateSeeder::class);
        $component = $this->makeComponent(6, [
            'egi' => 'HD785-7',
            'model_type' => 'HD785-7',
            'major_category' => 'TC/Transmission',
        ]);

        $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id))
            ->assertOk();

        $checksheet = $component->fresh()->checksheets->where('stage_number', 6)->first();
        $this->assertNotNull($checksheet);
        $this->assertNotEmpty($checksheet->items);
    }

    // ===== Stage 7: RFU =====

    public function test_stage_7_menampilkan_panel_penutup_rfu(): void
    {
        $component = $this->makeComponent(7, ['status' => 'Ready for Use']);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee('id="rfu-panel"', false);
        $response->assertSee('Ready for Use (RFU)');
        $response->assertSee('Overhaul selesai');
    }
}
