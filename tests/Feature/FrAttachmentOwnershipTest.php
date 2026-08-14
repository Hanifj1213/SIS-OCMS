<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Models\User;
use App\Services\FrAttachmentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FrAttachmentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Role::findOrCreate('Mechanic', 'web');
        $this->mechanic = User::create([
            'name' => 'Mekanik Attach',
            'nik' => 'ATT-'.random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');
    }

    private function makeComponent(): Component
    {
        return Component::create([
            'serial_number' => 'ATT-'.random_int(1000, 9999),
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'major_category' => 'Engine',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
    }

    private function seedImage(string $dir = 'fr-sketches', string $name = 'owned.jpg'): string
    {
        Storage::disk('public')->put("{$dir}/{$name}", $this->pngBytes());

        return "storage/{$dir}/{$name}";
    }

    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
    }

    private function makeFr(Component $component, array $overrides = []): FabricationRequest
    {
        return FabricationRequest::create(array_merge([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/'.random_int(1000, 9999).'/VIII/2026/INT',
            'part_name' => 'TEST PART',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
        ], $overrides));
    }

    public function test_resolver_menolak_path_traversal_dan_absolute(): void
    {
        $resolver = app(FrAttachmentResolver::class);

        $this->assertNull($resolver->normalizeClientPath('storage/fr-sketches/../secrets.txt'));
        $this->assertNull($resolver->normalizeClientPath('C:/Windows/win.ini'));
        $this->assertNull($resolver->normalizeClientPath('https://evil.test/payload.jpg'));
        $this->assertNull($resolver->normalizeClientPath('/etc/passwd'));
    }

    public function test_update_menolak_reuse_gambar_fr_lain(): void
    {
        $component = $this->makeComponent();
        $foreignPath = $this->seedImage('fr-sketches', 'foreign.jpg');

        $this->makeFr($component, [
            'fr_number' => 'FR/SIS/RC/0001/VIII/2026/INT',
            'images' => [['path' => $foreignPath, 'x' => 2, 'y' => 3, 'w' => 40]],
        ]);

        $target = $this->makeFr($component, [
            'fr_number' => 'FR/SIS/RC/0002/VIII/2026/INT',
        ]);

        $response = $this->actingAs($this->mechanic)->put(
            route('components.fr.update', [$component->comp_id, $target->fr_id]),
            [
                'part_name' => 'TEST PART',
                'qty' => 1,
                'work_type' => 'repair',
                'images' => [
                    ['path' => $foreignPath, 'x' => 2, 'y' => 3, 'w' => 40],
                ],
            ],
        );

        $response->assertSessionHasErrors('images');
        $this->assertNull($target->fresh()->images);
    }

    public function test_update_mempertahankan_gambar_milik_fr_sendiri(): void
    {
        $component = $this->makeComponent();
        $ownedPath = $this->seedImage('fr-sketches', 'mine.jpg');

        $fr = $this->makeFr($component, [
            'images' => [['path' => $ownedPath, 'x' => 2, 'y' => 3, 'w' => 40]],
        ]);

        $this->actingAs($this->mechanic)->put(
            route('components.fr.update', [$component->comp_id, $fr->fr_id]),
            [
                'part_name' => 'TEST PART',
                'qty' => 1,
                'work_type' => 'repair',
                'images' => [
                    ['path' => $ownedPath, 'x' => 5, 'y' => 6, 'w' => 42],
                ],
            ],
        )->assertRedirect();

        $images = $fr->fresh()->images;
        $this->assertSame($ownedPath, $images[0]['path']);
        $this->assertSame(5.0, (float) $images[0]['x']);
    }

    public function test_pdf_melewati_path_tidak_valid_dalam_metadata(): void
    {
        $component = $this->makeComponent();
        $ownedPath = $this->seedImage('fr-sketches', 'pdf.jpg');

        $fr = $this->makeFr($component, [
            'images' => [
                ['path' => $ownedPath, 'x' => 2, 'y' => 3, 'w' => 40],
                ['path' => 'storage/fr-sketches/not-found.jpg', 'x' => 50, 'y' => 3, 'w' => 40],
                ['path' => 'storage/fr-sketches/../evil.jpg', 'x' => 50, 'y' => 3, 'w' => 40],
            ],
        ]);

        $resolved = app(FrAttachmentResolver::class)->resolveImagesForPdf($fr);

        $this->assertCount(1, $resolved);
        $this->assertSame($ownedPath, $resolved[0]['path']);
    }

    public function test_mime_palsu_ditolak(): void
    {
        Storage::disk('public')->put('fr-sketches/fake.jpg', 'not-an-image');
        $resolver = app(FrAttachmentResolver::class);

        $this->assertNull($resolver->normalizeClientPath('storage/fr-sketches/fake.jpg'));
    }

    public function test_unggahan_file_valid_diterima(): void
    {
        $component = $this->makeComponent();

        $response = $this->actingAs($this->mechanic)->post(
            route('components.fr.storeSingle', $component->comp_id),
            [
                'part_name' => 'PART UPLOAD',
                'qty' => 1,
                'work_type' => 'repair',
                'images' => [[
                    'data' => 'data:image/png;base64,'.base64_encode($this->pngBytes()),
                    'x' => 2,
                    'y' => 3,
                    'w' => 40,
                ]],
            ],
        );

        $response->assertRedirect();
        $fr = FabricationRequest::where('part_name', 'PART UPLOAD')->first();
        $this->assertNotNull($fr);
        $this->assertCount(1, $fr->imageList());
        $this->assertStringStartsWith('storage/fr-sketches/', $fr->imageList()[0]['path']);
    }
}
