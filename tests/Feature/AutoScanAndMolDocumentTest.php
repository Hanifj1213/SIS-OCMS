<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Models\PartRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AutoScanAndMolDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function mechanic(): User
    {
        Role::findOrCreate('Mechanic', 'web');
        $user = User::create([
            'name' => 'Mekanik Scan',
            'nik' => 'SCAN-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        return $user;
    }

    private function makeComponent(): Component
    {
        return Component::create([
            'serial_number' => 'AUTOSCAN-' . random_int(1000, 9999),
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'major_category' => 'Engine',
            'unit_code' => 'DZ040-0099',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
    }

    public function test_scan_langsung_membuat_fr_dan_pr_tanpa_tahap_kandidat(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        // Tambah keputusan internal di inspection_details
        $component->inspectionDetails()->create([
            'part_name' => 'SHAFT REPAIR',
            'decision' => 'Repair',
        ]);
        $component->inspectionDetails()->create([
            'part_name' => 'BEARING REPLACE',
            'decision' => 'Replace',
        ]);

        $response = $this->actingAs($user)->postJson(route('components.fr.scan', $component->comp_id));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('total_fr', 1);
        $response->assertJsonPath('total_pr', 1);

        // FR langsung dibuat di DB
        $this->assertDatabaseHas('fabrication_requests', [
            'comp_id' => $component->comp_id,
            'part_name' => 'SHAFT REPAIR',
            'work_type' => 'repair',
            'status' => 'draft',
        ]);

        // PR (MOL) langsung dibuat di DB
        $this->assertDatabaseHas('part_requests', [
            'comp_id' => $component->comp_id,
            'part_name' => 'BEARING REPLACE',
            'status' => 'Pending',
        ]);
    }

    public function test_upload_dan_delete_dokumen_mol(): void
    {
        Storage::fake('public');

        $user = $this->mechanic();
        $component = $this->makeComponent();

        $file = UploadedFile::fake()->create('mol_form.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->postJson(
            route('components.mol.upload-document', $component->comp_id),
            ['mol_document' => $file]
        );

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $component->refresh();
        $this->assertNotNull($component->mol_document_path);

        // Hapus dokumen
        $deleteResponse = $this->actingAs($user)->deleteJson(
            route('components.mol.delete-document', $component->comp_id)
        );

        $deleteResponse->assertOk();
        $component->refresh();
        $this->assertNull($component->mol_document_path);
    }

    public function test_tombol_sdr_dan_llk_parts_tampil_di_halaman_show(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $response = $this->actingAs($user)->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee('📊 SDR');
        $response->assertSee('https://docs.google.com/spreadsheets/d/1HvxiqXGEvH_nscYugPjOEfgIdq9Ps9nEyKqt_vNrd_8/edit?usp=sharing', false);
        $response->assertSee('https://llk-parts.ru/#', false);
        $response->assertSee('LLK Parts Catalog');
        $response->assertSee('tab-fr-btn');
        $response->assertSee('tab-mol-btn');

        // Jika komponen sudah punya gsheet_sdr_url terduplikasi, tombol SDR memakai URL terduplikasi tersebut
        $component->update(['gsheet_sdr_url' => 'https://docs.google.com/spreadsheets/d/SDR_DUPLICATED_ID/edit']);
        $resCopied = $this->actingAs($user)->get(route('components.show', $component->comp_id));
        $resCopied->assertOk();
        $resCopied->assertSee('https://docs.google.com/spreadsheets/d/SDR_DUPLICATED_ID/edit', false);
    }
}
