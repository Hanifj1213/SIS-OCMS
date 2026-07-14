<?php

namespace Database\Seeders;

use App\Models\ChecksheetTemplate;
use Illuminate\Database\Seeder;

class ChecksheetTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // =============================================
        // ENGINE — Stage 1: Receiving Inspection Sheet
        // Berdasarkan dokumen SIS PRC asli (64 item)
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [
                    // === Right Side View (Item 1-27) ===
                    ['id' => 'RCV-001', 'group' => 'Right Side View', 'label' => 'Painting (Y/B)'],
                    ['id' => 'RCV-002', 'group' => 'Right Side View', 'label' => 'Turbocharger (P/N: 6502-51-5020)'],
                    ['id' => 'RCV-003', 'group' => 'Right Side View', 'label' => 'Tube support'],
                    ['id' => 'RCV-004', 'group' => 'Right Side View', 'label' => 'Hose Air cleaner'],
                    ['id' => 'RCV-005', 'group' => 'Right Side View', 'label' => 'Water temperature Sensor Assy'],
                    ['id' => 'RCV-006', 'group' => 'Right Side View', 'label' => 'Cover safety Exhaust Manifold (Heat Shield Assy)'],
                    ['id' => 'RCV-007', 'group' => 'Right Side View', 'label' => 'Name plate engine'],
                    ['id' => 'RCV-008', 'group' => 'Right Side View', 'label' => 'Starting motor (2pcs)'],
                    ['id' => 'RCV-009', 'group' => 'Right Side View', 'label' => 'Oil cooler (2pcs)'],
                    ['id' => 'RCV-010', 'group' => 'Right Side View', 'label' => 'Tube drain Turbocharger'],
                    ['id' => 'RCV-011', 'group' => 'Right Side View', 'label' => 'Oil pan'],
                    ['id' => 'RCV-012', 'group' => 'Right Side View', 'label' => 'Valve water Drain Cock'],
                    ['id' => 'RCV-013', 'group' => 'Right Side View', 'label' => 'Water inlet cover plate'],
                    ['id' => 'RCV-014', 'group' => 'Right Side View', 'label' => 'Water pump assy'],
                    ['id' => 'RCV-015', 'group' => 'Right Side View', 'label' => 'Tube Exhaust Gas EGR Re-Circulation'],
                    ['id' => 'RCV-016', 'group' => 'Right Side View', 'label' => 'Thermostat Housing'],
                    ['id' => 'RCV-017', 'group' => 'Right Side View', 'label' => 'Exhaust manifold'],
                    ['id' => 'RCV-018', 'group' => 'Right Side View', 'label' => 'Exhaust temperature sensor (2pcs)'],
                    ['id' => 'RCV-019', 'group' => 'Right Side View', 'label' => 'Air cleaner'],
                    ['id' => 'RCV-020', 'group' => 'Right Side View', 'label' => 'Bracket Air Cleaner'],
                    ['id' => 'RCV-021', 'group' => 'Right Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-022', 'group' => 'Right Side View', 'label' => 'Flywheel'],
                    ['id' => 'RCV-023', 'group' => 'Right Side View', 'label' => 'Rear Bracket'],
                    ['id' => 'RCV-024', 'group' => 'Right Side View', 'label' => 'Engine stand'],
                    ['id' => 'RCV-025', 'group' => 'Right Side View', 'label' => 'Sensor Revolution'],
                    ['id' => 'RCV-026', 'group' => 'Right Side View', 'label' => 'Heat Tab position'],
                    ['id' => 'RCV-027', 'group' => 'Right Side View', 'label' => 'Main harness'],

                    // === Left Side View (Item 28-46) ===
                    ['id' => 'RCV-028', 'group' => 'Left Side View', 'label' => 'Oil pressure sensor Assy'],
                    ['id' => 'RCV-029', 'group' => 'Left Side View', 'label' => 'Oil Temperature sensor assy'],
                    ['id' => 'RCV-030', 'group' => 'Left Side View', 'label' => 'Oil Filter'],
                    ['id' => 'RCV-031', 'group' => 'Left Side View', 'label' => 'Tube Air'],
                    ['id' => 'RCV-032', 'group' => 'Left Side View', 'label' => 'Air Connector Intake manifold'],
                    ['id' => 'RCV-033', 'group' => 'Left Side View', 'label' => 'Alternator Assy (90) (P/N: 600-861-9120)'],
                    ['id' => 'RCV-034', 'group' => 'Left Side View', 'label' => 'Bracket Belt drive Guard'],
                    ['id' => 'RCV-035', 'group' => 'Left Side View', 'label' => 'AC Compressor Assy'],
                    ['id' => 'RCV-036', 'group' => 'Left Side View', 'label' => 'Air Compressor Assy (P/N: 6210-81-3122)'],
                    ['id' => 'RCV-037', 'group' => 'Left Side View', 'label' => 'Corrosion resistor'],
                    ['id' => 'RCV-038', 'group' => 'Left Side View', 'label' => 'Blowby Sensor'],
                    ['id' => 'RCV-039', 'group' => 'Left Side View', 'label' => 'Gauge Oil (STD: H+0~10mm)'],
                    ['id' => 'RCV-040', 'group' => 'Left Side View', 'label' => 'Tube Oil Filter Cap'],
                    ['id' => 'RCV-041', 'group' => 'Left Side View', 'label' => 'Oil Level sensor'],
                    ['id' => 'RCV-042', 'group' => 'Left Side View', 'label' => 'Drain Plug With Valve'],
                    ['id' => 'RCV-043', 'group' => 'Left Side View', 'label' => 'Priming Pump Assy With Cover'],
                    ['id' => 'RCV-044', 'group' => 'Left Side View', 'label' => 'Tube Inlet'],
                    ['id' => 'RCV-045', 'group' => 'Left Side View', 'label' => 'Tube Outlet'],
                    ['id' => 'RCV-046', 'group' => 'Left Side View', 'label' => 'Engine Controller Assy (ECM)'],

                    // === Rear Side View (Item 47-64) ===
                    ['id' => 'RCV-047', 'group' => 'Rear Side View', 'label' => 'Fuel Pre Filter'],
                    ['id' => 'RCV-048', 'group' => 'Rear Side View', 'label' => 'Fuel Supply Pump Assy (P/N: 6245-71-1100)'],
                    ['id' => 'RCV-049', 'group' => 'Rear Side View', 'label' => 'Feed Pump'],
                    ['id' => 'RCV-050', 'group' => 'Rear Side View', 'label' => 'Dust Indicator'],
                    ['id' => 'RCV-051', 'group' => 'Rear Side View', 'label' => 'Electrical Wiring Harness'],
                    ['id' => 'RCV-052', 'group' => 'Rear Side View', 'label' => 'AIR Bleed Valve'],
                    ['id' => 'RCV-053', 'group' => 'Rear Side View', 'label' => 'Fuel Filter Assy'],
                    ['id' => 'RCV-054', 'group' => 'Rear Side View', 'label' => 'Front Hanger plate'],
                    ['id' => 'RCV-055', 'group' => 'Rear Side View', 'label' => 'Tube Water Outlet'],
                    ['id' => 'RCV-056', 'group' => 'Rear Side View', 'label' => 'Cooler mounting'],
                    ['id' => 'RCV-057', 'group' => 'Rear Side View', 'label' => 'Tube Water'],
                    ['id' => 'RCV-058', 'group' => 'Rear Side View', 'label' => 'Block Water Housing'],
                    ['id' => 'RCV-059', 'group' => 'Rear Side View', 'label' => 'Damper Assy'],
                    ['id' => 'RCV-060', 'group' => 'Rear Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-061', 'group' => 'Rear Side View', 'label' => 'Oil Pump Assy'],
                    ['id' => 'RCV-062', 'group' => 'Rear Side View', 'label' => 'Alternator Drive pulley'],
                    ['id' => 'RCV-063', 'group' => 'Rear Side View', 'label' => 'Tension Pulley Assy'],
                    ['id' => 'RCV-064', 'group' => 'Rear Side View', 'label' => 'V-belt Alternator'],
                ],
            ]
        );
    }
}
