<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentChecksheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * P1.2 — Stored XSS pada checksheet interaktif.
 *
 * Data checksheet (label, group, standard, source) berasal dari template dan
 * input pengguna. Renderer sisi klien wajib memakai createElement/textContent
 * sehingga payload tersimpan tidak pernah menjadi markup yang dieksekusi, dan
 * tidak boleh ada inline event handler yang dibangun dari data.
 */
class ChecksheetXssTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;
    private Component $component;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        Role::findOrCreate('Mechanic', 'web');

        $this->mechanic = User::create([
            'name' => 'Mekanik XSS',
            'nik' => 'XSS-MEC-'.random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');

        $this->component = Component::create([
            'serial_number' => 'XSS-'.random_int(10000, 99999),
            'egi' => 'SA12V140E-1',
            'model_type' => 'SA12V140E-1',
            'major_category' => 'Engine',
            'current_stage' => 1,
            'status' => 'On Progress',
        ]);
        $this->component->overhaulLogs()->create([
            'stage_number' => 1,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now(),
        ]);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function payloadProvider(): array
    {
        return [
            ['<script>alert(1)</script>'],
            ['<img src=x onerror=alert(1)>'],
            ['" onmouseover="alert(1)'],
            ["' onclick='alert(1)"],
            ['`${alert(1)}`'],
            ['</div><svg/onload=alert(1)>'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payloadProvider')]
    public function test_payload_pada_label_tidak_dirender_sebagai_markup(string $payload): void
    {
        ComponentChecksheet::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 1,
            'items' => [[
                'id' => 'RCV-001',
                'group' => $payload,
                'label' => $payload,
                'standard' => $payload,
                'source' => $payload,
            ]],
            'answers' => [],
        ]);

        $response = $this->actingAs($this->mechanic)
            ->get(route('components.show', $this->component->comp_id));

        $response->assertOk();
        $html = $response->getContent();

        // Payload hanya boleh muncul dalam bentuk JSON ter-escape di dalam
        // blok <script> data — bukan sebagai tag/atribut aktif.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringNotContainsString('<svg/onload=alert(1)>', $html);
    }

    public function test_renderer_tidak_membangun_inline_handler_dari_data(): void
    {
        ComponentChecksheet::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 1,
            'items' => [[
                'id' => 'RCV-001',
                'group' => 'Umum',
                'label' => 'Item normal',
                'source' => 'D375-6 EG MAINLINE.pdf p.1',
            ]],
            'answers' => [],
        ]);

        $html = $this->actingAs($this->mechanic)
            ->get(route('components.show', $this->component->comp_id))
            ->assertOk()
            ->getContent();

        // Tidak boleh ada handler inline yang dirakit dari data item/gambar.
        $this->assertStringNotContainsString('onclick="csOpenLightbox(\'${', $html);
        $this->assertStringNotContainsString('onclick="csGoToItem(${', $html);
        $this->assertStringNotContainsString('onclick="csRemoveItem(${', $html);

        // Renderer memakai DOM API, bukan innerHTML untuk data dinamis.
        $this->assertStringContainsString('createElement', $html);
        $this->assertStringContainsString('textContent', $html);
    }

    public function test_checksheet_normal_tetap_dapat_ditampilkan_dijawab_ditambah_dihapus(): void
    {
        $checksheet = ComponentChecksheet::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 1,
            'items' => [
                ['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Kondisi fisik'],
                ['id' => 'RCV-002', 'group' => 'Umum', 'label' => 'Kelengkapan part'],
            ],
            'answers' => [],
        ]);

        // Tampil
        $this->actingAs($this->mechanic)
            ->get(route('components.show', $this->component->comp_id))
            ->assertOk()
            ->assertSee('Kondisi fisik');

        // Jawab
        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$this->component->comp_id, 1]), [
                'item_id' => 'RCV-001',
                'answer' => 'good',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Tambah item custom
        $add = $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.addItem', [$this->component->comp_id, 1]), [
                'label' => 'Item custom baru',
                'group' => 'Custom Items',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $customId = $add->json('item.id');
        $this->assertNotNull($customId);

        // Hapus item custom
        $this->actingAs($this->mechanic)
            ->deleteJson(route('checksheet.removeItem', [$this->component->comp_id, 1]), [
                'item_id' => $customId,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $checksheet->refresh();
        $this->assertCount(2, $checksheet->items);
        $this->assertSame('good', $checksheet->answers['RCV-001']);
    }

    public function test_item_id_asing_dan_nilai_jawaban_tidak_valid_ditolak(): void
    {
        ComponentChecksheet::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 1,
            'items' => [['id' => 'RCV-001', 'group' => 'Umum', 'label' => 'Kondisi fisik']],
            'answers' => [],
        ]);

        // item_id yang tidak ada pada checksheet
        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$this->component->comp_id, 1]), [
                'item_id' => 'TIDAK-ADA',
                'answer' => 'good',
            ])
            ->assertStatus(422);

        // nilai jawaban di luar good/bad/none
        $this->actingAs($this->mechanic)
            ->postJson(route('checksheet.saveAnswer', [$this->component->comp_id, 1]), [
                'item_id' => 'RCV-001',
                'answer' => 'excellent',
            ])
            ->assertStatus(422);
    }
}
