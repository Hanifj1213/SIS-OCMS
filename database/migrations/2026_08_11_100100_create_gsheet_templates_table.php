<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapping template GSheet per kind × kategori × EGI dipindah dari
     * config/checksheet_gsheets.php ke database supaya Developer bisa
     * menambah EGI/komponen baru dari UI tanpa mengubah kode.
     */
    public function up(): void
    {
        Schema::create('gsheet_templates', function (Blueprint $table) {
            $table->id();
            // disassembly | measurement | subassy_disassembly |
            // subassy_measurement | sdr | assembly | testbench
            $table->string('kind');
            // NULL = default untuk semua kategori/EGI (mis. SDR)
            $table->string('major_category')->nullable();
            $table->string('egi')->nullable();
            $table->string('spreadsheet_id');
            $table->timestamps();

            $table->unique(['kind', 'major_category', 'egi'], 'gsheet_templates_kind_cat_egi_unique');
        });

        $this->importFromConfig();
    }

    public function down(): void
    {
        Schema::dropIfExists('gsheet_templates');
    }

    /**
     * Impor satu kali isi config lama sebagai data awal tabel.
     */
    private function importFromConfig(): void
    {
        $kinds = [
            'disassembly' => 'disassembly_templates',
            'measurement' => 'measurement_templates',
            'subassy_disassembly' => 'subassy_disassembly_templates',
            'subassy_measurement' => 'subassy_measurement_templates',
            'sdr' => 'sdr_templates',
            'assembly' => 'assembly_templates',
            'testbench' => 'testbench_templates',
        ];

        $now = now();
        $rows = [];

        foreach ($kinds as $kind => $configKey) {
            $bucket = config("checksheet_gsheets.{$configKey}", []);
            if (! is_array($bucket)) {
                continue;
            }

            foreach ($bucket as $category => $entry) {
                if (is_string($entry)) {
                    // Level kategori berupa string = default untuk kind ini
                    // (contoh: sdr_templates.default).
                    $rows[] = [
                        'kind' => $kind,
                        'major_category' => $category === 'default' ? null : $category,
                        'egi' => null,
                        'spreadsheet_id' => $entry,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    continue;
                }

                foreach ((array) $entry as $egi => $spreadsheetId) {
                    if (! is_string($spreadsheetId) || $spreadsheetId === '') {
                        continue;
                    }
                    $rows[] = [
                        'kind' => $kind,
                        'major_category' => (string) $category,
                        'egi' => strtoupper((string) $egi),
                        'spreadsheet_id' => $spreadsheetId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('gsheet_templates')->insert($chunk);
        }
    }
};
