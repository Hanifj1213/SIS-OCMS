<?php

namespace Database\Seeders;

use App\Models\ChecksheetTemplate;
use Illuminate\Database\Seeder;

/**
 * Stage 7 (RFU/Delivery) — checksheet internal seperti Receiving.
 *
 * Item diambil dari template asli:
 * CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/ENGINE/SA12V140E-1/MAINLINE/DELIVERY/
 * DELIVERY ENGINE SA12V140E-1.ods (Delivery Inspection Sheet, RC/QR06/EG/24/04).
 *
 * Nomor item mengikuti sheet asli (51-52 tidak ada teksnya di file sumber —
 * hanya callout gambar), grup mengikuti sudut pandang sketch.
 */
class DeliveryChecksheetTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $src = 'DELIVERY ENGINE SA12V140E-1.ods';

        $define = function (int $seq, string $group, string $label, ?int $number) use ($src): array {
            $item = [
                'id' => sprintf('DLV-%03d', $seq),
                'group' => $group,
                'label' => $label,
                'source' => $src,
            ];
            if ($number !== null) {
                $item['number'] = $number;
            }

            return $item;
        };

        $seq = 0;
        $items = [];

        // === R.H. View (1-17) ===
        foreach ([
            [1, 'Painting'],
            [2, 'Production No.'],
            [3, 'Air Crossover Housing'],
            [4, 'Turbocharger LH (catat S/N)'],
            [5, 'Turbocharger RH (catat S/N)'],
            [6, 'Water outlet connection RH'],
            [7, 'Lifting Bracket'],
            [8, 'Fan belt idler assembly'],
            [9, 'After cooler water inlet tube'],
            [10, 'Fan belt idler pulley'],
            [11, 'Water pump'],
            [12, 'Water inlet connection'],
            [13, 'Lubricating oil filler tube'],
            [14, 'Fuel lift pump'],
            [15, 'Lubricating oil drain RH'],
            [16, 'Fuel filters'],
            [17, 'Fuel injection pump LH (catat S/N)'],
        ] as [$no, $label]) {
            $items[] = $define(++$seq, 'R.H. View', $label, $no);
        }

        // === L.H. View (18-37) ===
        foreach ([
            [18, 'Lubricating oil by pass filters'],
            [19, 'Lubricating oil transfer tube'],
            [20, 'Air intake manifold'],
            [21, 'Turbocharger inlet connection'],
            [22, 'Turbocharger outlet connection'],
            [23, 'After cooler housing'],
            [24, 'Cam follower cover'],
            [25, 'High pressure fuel supply lines'],
            [26, 'Oil pressure sensor'],
            [27, 'Starting Motor (catat S/N)'],
            [28, 'Lubricating oil drain LH'],
            [29, 'Fuel lift pump LH'],
            [30, 'Fuel Injection pump RH (catat S/N)'],
            [31, 'Vibration damper'],
            [32, 'Fuel pump drive'],
            [33, 'Crankcase Breather'],
            [34, 'Coolant temperature sensor'],
            [35, 'Fan hub'],
            [36, 'Thermostat housing'],
            [37, 'Water vent tubes'],
        ] as [$no, $label]) {
            $items[] = $define(++$seq, 'L.H. View', $label, $no);
        }

        // === Rear View (38-50) ===
        foreach ([
            [38, 'Flywheel housing'],
            [39, 'Flywheel'],
            [40, 'Engine position sensor (Industrial)'],
            [null, 'Engine speed sensor (G.drive / Gen set)'],
            [41, 'Engine speed sensor (Industrial)'],
            [42, 'Alternator (catat S/N)'],
            [43, 'Fan hub'],
            [44, 'Air crossover'],
            [45, 'Coolant temperature sensor'],
            [46, 'Oil pan'],
            [47, 'Fan idler tensioner pulley'],
            [48, 'Corrosion Resistors'],
            [49, 'Accessory drive'],
            [50, 'Full-flow oil filters'],
        ] as [$no, $label]) {
            $items[] = $define(++$seq, 'Rear View', $label, $no);
        }

        // === Front View (53-56) ===
        foreach ([
            [53, 'Front PTO'],
            [54, 'Air Compressor'],
            [55, 'Wiring harness'],
            [56, 'Oil Level'],
        ] as [$no, $label]) {
            $items[] = $define(++$seq, 'Front View', $label, $no);
        }

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 7, 'egi_model' => 'SA12V140E-1'],
            [
                'template_name' => 'Engine Delivery Inspection Sheet (RC/QR06/EG/24/04)',
                'items' => $items,
            ]
        );
    }
}
