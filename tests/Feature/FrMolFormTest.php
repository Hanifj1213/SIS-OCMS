<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Models\User;
use App\Services\FrAnnotationRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Alur form dokumen: FR PLO/09/F-021 (create/store/edit/update) dan
 * MOL (form kosong + simpan baris jadi Part Request).
 */
class FrMolFormTest extends TestCase
{
    use RefreshDatabase;

    private function mechanic(): User
    {
        Role::findOrCreate('Mechanic', 'web');

        $user = User::create([
            'name' => 'Mekanik Form',
            'nik' => 'FORM-'.random_int(1000, 9999),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        return $user;
    }

    private function makeComponent(): Component
    {
        return Component::create([
            'serial_number' => 'FORM-COMP-'.random_int(1000, 9999),
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'major_category' => 'Engine',
            'component_model' => 'POWER MODULE',
            'unit_code' => 'DZ040-0059',
            'site_district' => 'ADMO',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
    }

    public function test_form_fr_kosong_terisi_dari_kandidat_scan(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $response = $this->actingAs($user)->get(route('components.fr.create', $component->comp_id).'?'.http_build_query([
            'part_name' => 'SPACER',
            'part_number' => '17A-15-42710',
            'section' => 'DISASSY RH',
            'qty' => 2,
            'work_type' => 'repair',
            'instruction' => 'ABNORMAL WORN CONTACT BEARING',
            'source' => 'gsheet',
        ]));

        $response->assertOk();
        $response->assertSee('FABRICATION REQUEST');
        $response->assertSee('PLO/09/F-021');
        $response->assertSee('SPACER');
        $response->assertSee('17A-15-42710');
        $response->assertSee('ABNORMAL WORN CONTACT BEARING');

        // Garis titik di bawah kolom nama penanda tangan pernah hilang karena
        // selnya dibiarkan kosong — pastikan titiknya benar-benar dirender.
        $response->assertSee('<span>'.str_repeat('.', 60).'</span>', false);
    }

    public function test_store_single_membuat_fr_dengan_nomor_resmi_dan_field_plo(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $response = $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'FLANGE',
            'part_number' => '17A-21-12132',
            'section' => 'DISASSY RH',
            'source' => 'gsheet',
            'qty' => 1,
            'work_type' => 'repair',
            'instruction' => 'CUTTING SURFACE FLANGE',
            'ro_number' => '2600027686',
            'location_site' => 'ADMO',
            'work_order_for' => 'JATMIKO',
            'attn' => 'ARY S PRAJA',
            'brand' => 'KMT',
            'unit_price' => 150000,
            'labour_cost' => 50000,
            'request_date' => '2026-07-03',
        ]);

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();

        $response->assertRedirect(route('components.fr.edit', [$component->comp_id, $fr->fr_id]));
        $this->assertMatchesRegularExpression('#^FR/SIS/RC/\d{4}/[IVX]+/\d{4}/INT$#', $fr->fr_number);
        $this->assertSame('FLANGE', $fr->part_name);
        $this->assertSame('2600027686', $fr->ro_number);
        $this->assertSame('KMT', $fr->brand);
        $this->assertSame('150000.00', (string) $fr->unit_price);
        $this->assertSame('draft', $fr->status);
    }

    public function test_update_fr_menyimpan_biaya_dan_catatan(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $fr = FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/0001/VIII/2026/INT',
            'part_name' => 'CYLINDER',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->put(route('components.fr.update', [$component->comp_id, $fr->fr_id]), [
            'part_name' => 'CYLINDER',
            'qty' => 2,
            'work_type' => 'fabrikasi',
            'instruction' => 'ABNORMAL PITTING CONTACT SEAL',
            'labour_cost' => 250000,
            'note' => 'Supplier ajukan quotation',
        ]);

        $response->assertRedirect(route('components.show', $component->comp_id));

        $fr->refresh();
        $this->assertSame('fabrikasi', $fr->work_type);
        $this->assertSame(2, $fr->qty);
        $this->assertSame('250000.00', (string) $fr->labour_cost);
        $this->assertSame('Supplier ajukan quotation', $fr->note);
    }

    public function test_pdf_fr_menggunakan_layout_plo(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $fr = FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/0002/VIII/2026/INT',
            'part_name' => 'SPACER',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
            'unit_price' => 100000,
            'labour_cost' => 25000,
            'annotations' => [
                [
                    'type' => 'double_arrow',
                    'x1' => 20,
                    'y1' => 30,
                    'x2' => 80,
                    'y2' => 30,
                    'color' => '#ef4444',
                    'stroke' => 2,
                ],
                [
                    'type' => 'text',
                    'x' => 44,
                    'y' => 20,
                    'text' => '120mm',
                    'color' => '#ffffff',
                    'font_size' => 5,
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('components.fr.pdf', [$component->comp_id, $fr->fr_id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        // Cetak mengubah status draft → printed
        $this->assertSame('printed', $fr->fresh()->status);
    }

    public function test_jenis_pekerjaan_boleh_lebih_dari_satu(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair', 'fabrikasi'],
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();

        $this->assertSame(['repair', 'fabrikasi'], $fr->workTypes());
        $this->assertTrue($fr->hasWorkType('repair'));
        $this->assertTrue($fr->hasWorkType('fabrikasi'));
        $this->assertFalse($fr->hasWorkType('modifikasi'));
        // Kolom lama tetap terisi agar tampilan/daftar lama tidak rusak
        $this->assertSame('repair', $fr->work_type);
    }

    public function test_work_type_tunggal_dari_panel_scan_masih_diterima(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'FLANGE',
            'qty' => 1,
            'work_type' => 'modifikasi',
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();

        $this->assertSame(['modifikasi'], $fr->workTypes());
        $this->assertSame('modifikasi', $fr->work_type);
    }

    public function test_kode_formulir_bisa_disunting_dan_punya_nilai_bawaan(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'form_no' => 'PLO/09/F-021',
            'sop_no' => 'PLO/09/000/SOP',
            'form_owner' => 'Plant Operation Dept.',
            'form_revision' => '2',
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();

        $this->assertSame('2', $fr->formCode('form_revision'));
        $this->assertSame('PLO/09/F-021', $fr->formCode('form_no'));

        // Kolom kosong jatuh ke nilai bawaan form cetak
        $fr->update(['form_owner' => null, 'form_revision' => '']);
        $this->assertSame('Plant Operation Dept.', $fr->fresh()->formCode('form_owner'));
        $this->assertSame('1', $fr->fresh()->formCode('form_revision'));
    }

    public function test_tanda_tangan_nama_tanggal_dan_for_tersimpan(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'signatures' => [
                'approved_by' => ['name' => 'JATMIKO', 'date' => '2026-01-19'],
                'ordered_by' => ['name' => 'PRAJA', 'date' => '2026-01-19'],
                'sent_by' => ['name' => '', 'date' => null],
            ],
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();

        $this->assertSame('JATMIKO', $fr->signature('approved_by')['name']);
        $this->assertSame('2026-01-19', $fr->signature('approved_by')['date']);

        $this->assertSame('PRAJA', $fr->signature('ordered_by')['name']);
        $this->assertSame('2026-01-19', $fr->signature('ordered_by')['date']);

        // Kolom kosong tidak disimpan supaya JSON tetap ringkas
        $this->assertArrayNotHasKey('sent_by', $fr->signatures ?? []);
    }

    public function test_edit_tidak_menghapus_tanda_tangan_yang_sudah_ada(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $fr = FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/0009/VIII/2026/INT',
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
            'signatures' => [
                'approved_by' => ['name' => 'JATMIKO', 'date' => '2026-01-19', 'image' => 'storage/fr-signatures/a.png'],
            ],
        ]);

        $this->actingAs($user)->put(route('components.fr.update', [$component->comp_id, $fr->fr_id]), [
            'part_name' => 'SHAFT',
            'qty' => 2,
            'work_types' => ['repair'],
            'signatures' => ['approved_by' => ['name' => 'JATMIKO', 'date' => '2026-01-19']],
        ])->assertRedirect();

        // Gambar tanda tangan tidak diunggah ulang, harus tetap ada
        $this->assertSame('storage/fr-signatures/a.png', $fr->fresh()->signature('approved_by')['image']);
    }

    public function test_identitas_unit_bisa_disunting_dan_default_ikut_komponen(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        // Form baru: nilai awal ikut data komponen
        $this->actingAs($user)
            ->get(route('components.fr.create', $component->comp_id))
            ->assertOk()
            ->assertSee('name="unit_model"', false)
            ->assertSee('name="component_model"', false)
            ->assertSee('name="unit_code"', false)
            ->assertSee('value="D155-6"', false);

        // Nilai suntingan menang atas data komponen
        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'unit_model' => 'HD785-7',
            'component_model' => 'TORQUE FLOW',
            'unit_code' => 'DT090-0146B',
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();

        $this->assertSame('HD785-7', $fr->identity('unit_model', $component));
        $this->assertSame('TORQUE FLOW', $fr->identity('component_model', $component));
        $this->assertSame('DT090-0146B', $fr->identity('unit_code', $component));

        // Dikosongkan kembali → jatuh ke data komponen
        $fr->update(['unit_model' => null, 'component_model' => '', 'unit_code' => null]);
        $fresh = $fr->fresh();
        $this->assertSame('D155-6', $fresh->identity('unit_model', $component));
        $this->assertSame('POWER MODULE', $fresh->identity('component_model', $component));
        $this->assertSame('DZ040-0059', $fresh->identity('unit_code', $component));
    }

    public function test_nomor_fr_pada_form_baru_memakai_pola_resmi(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $response = $this->actingAs($user)->get(route('components.fr.create', $component->comp_id));

        $response->assertOk();
        $response->assertSee('FR/SIS/RC/____/', false);
        $response->assertDontSee('nomor otomatis saat disimpan');
    }

    public function test_posisi_dan_ukuran_gambar_tersimpan(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'image_layout' => [
                'image' => ['x' => 10.5, 'y' => 20.25, 'w' => 35],
                'image_2' => ['x' => 55, 'y' => 8, 'w' => 44.75],
            ],
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();

        $this->assertSame(['x' => 10.5, 'y' => 20.25, 'w' => 35.0], $fr->imageBox('image'));
        $this->assertSame(['x' => 55.0, 'y' => 8.0, 'w' => 44.75], $fr->imageBox('image_2'));

        // Tanpa data tersimpan, dipakai komposisi bawaan
        $kosong = new FabricationRequest;
        $this->assertSame(['x' => 2.0, 'y' => 4.0, 'w' => 40.0], $kosong->imageBox('image'));
        $this->assertSame(['x' => 46.0, 'y' => 6.0, 'w' => 52.0], $kosong->imageBox('image_2'));
    }

    public function test_nomor_fr_bisa_disunting_dan_harus_unik(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        // Nomor ditulis manual
        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'fr_number' => 'FR/SIS/RC/0057/I/2026/INT',
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();
        $this->assertSame('FR/SIS/RC/0057/I/2026/INT', $fr->fr_number);

        // Nomor yang sama tidak boleh dipakai FR lain
        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'FLANGE',
            'qty' => 1,
            'work_types' => ['repair'],
            'fr_number' => 'FR/SIS/RC/0057/I/2026/INT',
        ])->assertSessionHasErrors('fr_number');

        // Dikosongkan = sistem memberi nomor otomatis
        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'TRUNION',
            'qty' => 1,
            'work_types' => ['repair'],
            'fr_number' => '',
        ])->assertRedirect();

        $auto = FabricationRequest::where('part_name', 'TRUNION')->firstOrFail();
        $this->assertMatchesRegularExpression('#^FR/SIS/RC/\d{4}/[IVX]+/\d{4}/INT$#', $auto->fr_number);
    }

    public function test_gambar_bisa_banyak_dan_tidak_menimpa_yang_sudah_ada(): void
    {
        Storage::fake('public');
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $png = 'data:image/png;base64,'.base64_encode(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==',
            true,
        ));

        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'TRUNION',
            'qty' => 1,
            'work_types' => ['repair'],
            'images' => [
                ['data' => $png, 'x' => 2, 'y' => 3, 'w' => 46],
                ['data' => $png, 'x' => 50, 'y' => 3, 'w' => 46],
                ['data' => $png, 'x' => 2, 'y' => 40, 'w' => 46],
            ],
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();
        $list = $fr->imageList();

        $this->assertCount(3, $list);
        $this->assertStringStartsWith('storage/fr-sketches/', $list[0]['path']);
        $this->assertStringStartsWith('storage/fr-sketches/', $list[1]['path']);
        $this->assertStringStartsWith('storage/fr-sketches/', $list[2]['path']);
        $this->assertSame(40.0, $list[2]['y']);

        $owned = collect($list)->map(fn ($img) => [
            'path' => $img['path'],
            'x' => $img['x'],
            'y' => $img['y'],
            'w' => $img['w'],
        ])->all();

        $five = array_merge($owned, [
            ['data' => $png, 'x' => 2, 'y' => 3, 'w' => 40],
            ['data' => $png, 'x' => 50, 'y' => 3, 'w' => 40],
        ]);

        $this->actingAs($user)->put(route('components.fr.update', [$component->comp_id, $fr->fr_id]), [
            'part_name' => 'TRUNION',
            'qty' => 1,
            'work_types' => ['repair'],
            'images' => $five,
        ])->assertRedirect();

        $this->assertCount(5, $fr->fresh()->imageList());
    }

    public function test_gambar_yang_dihapus_tidak_lagi_tersimpan(): void
    {
        Storage::fake('public');
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==',
            true,
        );
        Storage::disk('public')->put('fr-sketches/a.png', $pngBytes);
        Storage::disk('public')->put('fr-sketches/b.png', $pngBytes);

        $fr = FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/0080/VIII/2026/INT',
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
            'images' => [
                ['path' => 'storage/fr-sketches/a.png', 'x' => 2, 'y' => 3, 'w' => 46],
                ['path' => 'storage/fr-sketches/b.png', 'x' => 50, 'y' => 3, 'w' => 46],
            ],
        ]);

        $this->actingAs($user)->put(route('components.fr.update', [$component->comp_id, $fr->fr_id]), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'images' => [['path' => 'storage/fr-sketches/b.png', 'x' => 10, 'y' => 5, 'w' => 50]],
        ])->assertRedirect();

        $list = $fr->fresh()->imageList();
        $this->assertCount(1, $list);
        $this->assertSame('storage/fr-sketches/b.png', $list[0]['path']);
    }

    public function test_anotasi_garis_panah_dan_teks_ukuran_tersimpan(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $this->actingAs($user)->post(route('components.fr.storeSingle', $component->comp_id), [
            'part_name' => 'TRUNION',
            'qty' => 1,
            'work_types' => ['repair'],
            'annotations_present' => '1',
            'annotations' => [
                [
                    'type' => 'double_arrow',
                    'x1' => 25.5,
                    'y1' => 45,
                    'x2' => 78.25,
                    'y2' => 45,
                    'color' => '#ff0000',
                    'stroke' => 3,
                ],
                [
                    'type' => 'text',
                    'x' => 48,
                    'y' => 34.5,
                    'text' => '120mm',
                    'color' => '#ffffff',
                    'font_size' => 6,
                ],
            ],
        ])->assertRedirect();

        $fr = FabricationRequest::where('comp_id', $component->comp_id)->firstOrFail();
        $annotations = $fr->annotationList();

        $this->assertCount(2, $annotations);
        $this->assertSame('double_arrow', $annotations[0]['type']);
        $this->assertSame(78.25, $annotations[0]['x2']);
        $this->assertSame(3.0, $annotations[0]['stroke']);
        $this->assertSame('120mm', $annotations[1]['text']);
        $this->assertSame('#ffffff', $annotations[1]['color']);
    }

    public function test_form_edit_memuat_anotasi_tersimpan_sebagai_objek_interaktif(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();
        $fr = FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/0083/VIII/2026/INT',
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
            'annotations' => [
                [
                    'type' => 'line',
                    'x1' => 12.5,
                    'y1' => 24,
                    'x2' => 70.25,
                    'y2' => 68,
                    'color' => '#123456',
                    'stroke' => 4,
                ],
                [
                    'type' => 'text',
                    'x' => 33,
                    'y' => 40,
                    'text' => 'Bebas diedit',
                    'color' => '#654321',
                    'font_size' => 7,
                ],
            ],
        ]);

        $response = $this->actingAs($user)
            ->get(route('components.fr.edit', [$component->comp_id, $fr->fr_id]));

        $response->assertOk();
        $response->assertSee('"type":"line"', false);
        $response->assertSee('"text":"Bebas diedit"', false);
        $response->assertSee('data-annotation-id', false);
        $response->assertSee('syncControls', false);
        $response->assertSee('fr-canvas-editor', false);
        $response->assertSee('background: rgba(248, 250, 252, 0.52)', false);
    }

    public function test_semua_anotasi_bisa_dihapus_dari_form(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();
        $fr = FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/0082/VIII/2026/INT',
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
            'annotations' => [
                ['type' => 'text', 'x' => 10, 'y' => 20, 'text' => '15mm', 'color' => '#ef4444', 'font_size' => 5],
            ],
        ]);

        $this->actingAs($user)->put(route('components.fr.update', [$component->comp_id, $fr->fr_id]), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'annotations_present' => '1',
        ])->assertRedirect();

        $this->assertSame([], $fr->fresh()->annotationList());
    }

    public function test_anotasi_dirender_sebagai_lapisan_svg_untuk_pdf(): void
    {
        $svg = app(FrAnnotationRenderer::class)->svg([
            [
                'type' => 'arrow',
                'x1' => 10,
                'y1' => 20,
                'x2' => 80,
                'y2' => 20,
                'color' => '#ef4444',
                'stroke' => 2,
            ],
            [
                'type' => 'text',
                'x' => 40,
                'y' => 10,
                'text' => '15mm & Ø120',
                'color' => '#ffffff',
                'font_size' => 5,
            ],
        ]);

        $this->assertNotNull($svg);
        $this->assertStringContainsString('<line', $svg);
        $this->assertStringContainsString('<polygon', $svg);
        $this->assertStringContainsString('15mm &amp; Ø120', $svg);
    }

    public function test_posisi_tanda_tangan_tersimpan_dan_bisa_dihapus(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $fr = FabricationRequest::create([
            'comp_id' => $component->comp_id,
            'fr_number' => 'FR/SIS/RC/0081/VIII/2026/INT',
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'manual',
            'status' => 'draft',
            'signatures' => [
                'approved_by' => ['name' => 'JATMIKO', 'image' => 'storage/fr-signatures/a.png'],
            ],
        ]);

        $this->actingAs($user)->put(route('components.fr.update', [$component->comp_id, $fr->fr_id]), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'signatures' => ['approved_by' => ['name' => 'JATMIKO']],
            'signature_layout' => ['approved_by' => ['x' => 20, 'y' => 15, 'w' => 60]],
        ])->assertRedirect();

        $this->assertSame(['x' => 20.0, 'y' => 15.0, 'w' => 60.0], $fr->fresh()->signatureBox('approved_by'));

        // Klik kanan → hapus: kirim penanda remove_image
        $this->actingAs($user)->put(route('components.fr.update', [$component->comp_id, $fr->fr_id]), [
            'part_name' => 'SHAFT',
            'qty' => 1,
            'work_types' => ['repair'],
            'signatures' => ['approved_by' => ['name' => 'JATMIKO', 'remove_image' => '1']],
        ])->assertRedirect();

        $this->assertNull($fr->fresh()->signature('approved_by')['image']);
        // Nama tetap ada
        $this->assertSame('JATMIKO', $fr->fresh()->signature('approved_by')['name']);
    }

    public function test_form_mol_kosong_dapat_dibuka(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $response = $this->actingAs($user)->get(route('components.mol.create', $component->comp_id));

        $response->assertOk();
        $response->assertSee('MECHANIC ORDER LIST');
        $response->assertSee('Reason Code');
        $response->assertSee('MISS ORDER');
    }

    public function test_store_mol_menyimpan_header_dan_baris_part(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent();

        $response = $this->actingAs($user)->post(route('components.mol.store', $component->comp_id), [
            'mol_wo_number' => '2600027492',
            'mol_order_type' => 'APL / ADD 1',
            'mol_order_date' => '2026-07-03',
            'mol_note' => 'Code A-D khusus APL & ADD 1',
            'rows' => [
                [
                    'part_name' => 'AIR CLEANER',
                    'part_number' => '6211-12-4570',
                    'figure' => 'F-01',
                    'index_no' => '3',
                    'section' => 'AIR CLEANER',
                    'qty' => 1,
                    'order_code' => 'A',
                    'remarks' => 'Order normal',
                ],
                // Baris kosong harus diabaikan, bukan menghasilkan Part Request kosong
                ['part_name' => '', 'qty' => 1, 'order_code' => 'A'],
                [
                    'part_name' => 'OIL LEVEL GAUGE',
                    'part_number' => '6144-21-5650',
                    'qty' => 2,
                    'order_code' => 'F',
                ],
            ],
        ]);

        $response->assertRedirect(route('components.mol.create', $component->comp_id));

        $component->refresh();
        $this->assertSame('2600027492', $component->mol_wo_number);
        $this->assertSame('APL / ADD 1', $component->mol_order_type);

        $requests = $component->partRequests()->orderBy('req_id')->get();
        $this->assertCount(2, $requests);
        $this->assertSame('AIR CLEANER', $requests[0]->part_name);
        $this->assertSame('6211-12-4570', $requests[0]->part_number);
        $this->assertSame('A', $requests[0]->order_code);
        $this->assertSame('2600027492', $requests[0]->wo_number);
        $this->assertSame('OIL LEVEL GAUGE', $requests[1]->part_name);
        $this->assertSame('F', $requests[1]->order_code);
        $this->assertSame(2, $requests[1]->qty);
    }
}
