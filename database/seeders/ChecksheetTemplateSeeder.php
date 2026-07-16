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
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'D375-6'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [
                    // === Right Side View (Item 1-20) ===
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

                    // === Rear Side View (Item 21-27) — rear1.png ===
                    ['id' => 'RCV-021', 'group' => 'Rear Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-022', 'group' => 'Rear Side View', 'label' => 'Flywheel'],
                    ['id' => 'RCV-023', 'group' => 'Rear Side View', 'label' => 'Rear Bracket'],
                    ['id' => 'RCV-024', 'group' => 'Rear Side View', 'label' => 'Engine stand'],
                    ['id' => 'RCV-025', 'group' => 'Rear Side View', 'label' => 'Sensor Revolution'],
                    ['id' => 'RCV-026', 'group' => 'Rear Side View', 'label' => 'Heat Tab position'],
                    ['id' => 'RCV-027', 'group' => 'Rear Side View', 'label' => 'Main harness'],

                    // === Left Side View (Item 28-53) — left side.png ===
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
                    ['id' => 'RCV-047', 'group' => 'Left Side View', 'label' => 'Fuel Pre Filter'],
                    ['id' => 'RCV-048', 'group' => 'Left Side View', 'label' => 'Fuel Supply Pump Assy (P/N: 6245-71-1100)'],
                    ['id' => 'RCV-049', 'group' => 'Left Side View', 'label' => 'Feed Pump'],
                    ['id' => 'RCV-050', 'group' => 'Left Side View', 'label' => 'Dust Indicator'],
                    ['id' => 'RCV-051', 'group' => 'Left Side View', 'label' => 'Electrical Wiring Harness'],
                    ['id' => 'RCV-052', 'group' => 'Left Side View', 'label' => 'AIR Bleed Valve'],
                    ['id' => 'RCV-053', 'group' => 'Left Side View', 'label' => 'Fuel Filter Assy'],

                    // === Front Side View (Item 54-64) — rear2.png ===
                    ['id' => 'RCV-054', 'group' => 'Front Side View', 'label' => 'Front Hanger plate'],
                    ['id' => 'RCV-055', 'group' => 'Front Side View', 'label' => 'Tube Water Outlet'],
                    ['id' => 'RCV-056', 'group' => 'Front Side View', 'label' => 'Cooler mounting'],
                    ['id' => 'RCV-057', 'group' => 'Front Side View', 'label' => 'Tube Water'],
                    ['id' => 'RCV-058', 'group' => 'Front Side View', 'label' => 'Block Water Housing'],
                    ['id' => 'RCV-059', 'group' => 'Front Side View', 'label' => 'Damper Assy'],
                    ['id' => 'RCV-060', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-061', 'group' => 'Front Side View', 'label' => 'Oil Pump Assy'],
                    ['id' => 'RCV-062', 'group' => 'Front Side View', 'label' => 'Alternator Drive pulley'],
                    ['id' => 'RCV-063', 'group' => 'Front Side View', 'label' => 'Tension Pulley Assy'],
                    ['id' => 'RCV-064', 'group' => 'Front Side View', 'label' => 'V-belt Alternator'],
                ],
            ]
        );

        // =============================================
        // GENERIC FALLBACK — Engine Stage 1 (egi_model=NULL)
        // Digunakan saat EGI tidak dikenali / belum ada template khusus
        // Menggunakan items D375-6 sebagai default
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => null],
            [
                'template_name' => 'Engine Receiving Inspection Sheet (Generic)',
                'items' => [
                    // === Right Side View (Item 1-20) ===
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

                    // === Rear Side View (Item 21-27) ===
                    ['id' => 'RCV-021', 'group' => 'Rear Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-022', 'group' => 'Rear Side View', 'label' => 'Flywheel'],
                    ['id' => 'RCV-023', 'group' => 'Rear Side View', 'label' => 'Rear Bracket'],
                    ['id' => 'RCV-024', 'group' => 'Rear Side View', 'label' => 'Engine stand'],
                    ['id' => 'RCV-025', 'group' => 'Rear Side View', 'label' => 'Sensor Revolution'],
                    ['id' => 'RCV-026', 'group' => 'Rear Side View', 'label' => 'Heat Tab position'],
                    ['id' => 'RCV-027', 'group' => 'Rear Side View', 'label' => 'Main harness'],

                    // === Left Side View (Item 28-53) ===
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
                    ['id' => 'RCV-047', 'group' => 'Left Side View', 'label' => 'Fuel Pre Filter'],
                    ['id' => 'RCV-048', 'group' => 'Left Side View', 'label' => 'Fuel Supply Pump Assy (P/N: 6245-71-1100)'],
                    ['id' => 'RCV-049', 'group' => 'Left Side View', 'label' => 'Feed Pump'],
                    ['id' => 'RCV-050', 'group' => 'Left Side View', 'label' => 'Dust Indicator'],
                    ['id' => 'RCV-051', 'group' => 'Left Side View', 'label' => 'Electrical Wiring Harness'],
                    ['id' => 'RCV-052', 'group' => 'Left Side View', 'label' => 'AIR Bleed Valve'],
                    ['id' => 'RCV-053', 'group' => 'Left Side View', 'label' => 'Fuel Filter Assy'],

                    // === Front Side View (Item 54-64) ===
                    ['id' => 'RCV-054', 'group' => 'Front Side View', 'label' => 'Front Hanger plate'],
                    ['id' => 'RCV-055', 'group' => 'Front Side View', 'label' => 'Tube Water Outlet'],
                    ['id' => 'RCV-056', 'group' => 'Front Side View', 'label' => 'Cooler mounting'],
                    ['id' => 'RCV-057', 'group' => 'Front Side View', 'label' => 'Tube Water'],
                    ['id' => 'RCV-058', 'group' => 'Front Side View', 'label' => 'Block Water Housing'],
                    ['id' => 'RCV-059', 'group' => 'Front Side View', 'label' => 'Damper Assy'],
                    ['id' => 'RCV-060', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-061', 'group' => 'Front Side View', 'label' => 'Oil Pump Assy'],
                    ['id' => 'RCV-062', 'group' => 'Front Side View', 'label' => 'Alternator Drive pulley'],
                    ['id' => 'RCV-063', 'group' => 'Front Side View', 'label' => 'Tension Pulley Assy'],
                    ['id' => 'RCV-064', 'group' => 'Front Side View', 'label' => 'V-belt Alternator'],
                ],
            ]
        );

        // =============================================
        // HD785-7
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'HD785-7'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [

                    // === Left Side View ===
                    ['id' => 'RCV-001', 'group' => 'Left Side View', 'label' => 'Painting (Y/B)'],
                    ['id' => 'RCV-002', 'group' => 'Left Side View', 'label' => 'Tube Air Intake Connection LH'],
                    ['id' => 'RCV-003', 'group' => 'Left Side View', 'label' => 'Air Connector LH'],
                    ['id' => 'RCV-004', 'group' => 'Left Side View', 'label' => 'Fuel Filter Assy LH'],
                    ['id' => 'RCV-005', 'group' => 'Left Side View', 'label' => 'Engine Name Plate'],
                    ['id' => 'RCV-006', 'group' => 'Left Side View', 'label' => 'Tube, Breather With Hose LH'],
                    ['id' => 'RCV-007', 'group' => 'Left Side View', 'label' => 'Fuel Supply Pump Assy LH (P/N : 6219-71-1110)'],
                    ['id' => 'RCV-008', 'group' => 'Left Side View', 'label' => 'Tube Oil Filler Cap'],
                    ['id' => 'RCV-009', 'group' => 'Left Side View', 'label' => 'Gauge Oil (STD : H+ 0~10 mm)'],
                    ['id' => 'RCV-010', 'group' => 'Left Side View', 'label' => 'Engine Stand ( Original / Std )'],
                    ['id' => 'RCV-011', 'group' => 'Left Side View', 'label' => 'Oil Level Sensor Assy'],
                    ['id' => 'RCV-012', 'group' => 'Left Side View', 'label' => 'Drain Oil Plug'],
                    ['id' => 'RCV-013', 'group' => 'Left Side View', 'label' => 'Priming Pump Assy With Cover LH'],
                    ['id' => 'RCV-014', 'group' => 'Left Side View', 'label' => 'Warning Sticker Oil Level'],
                    ['id' => 'RCV-015', 'group' => 'Left Side View', 'label' => 'Bracket Mounting'],
                    ['id' => 'RCV-016', 'group' => 'Left Side View', 'label' => 'Quality passed Inspection Tag'],
                    ['id' => 'RCV-017', 'group' => 'Left Side View', 'label' => 'Caution Tag'],
                    ['id' => 'RCV-018', 'group' => 'Left Side View', 'label' => 'Oil Temperature Sensor'],
                    ['id' => 'RCV-019', 'group' => 'Left Side View', 'label' => 'Oil Pressure Sensor'],

                    // === Rear Side View ===
                    ['id' => 'RCV-020', 'group' => 'Rear Side View', 'label' => 'Starting Motor Assy (7.5 KW) (P/N : 600-813-7542)'],
                    ['id' => 'RCV-021', 'group' => 'Rear Side View', 'label' => 'Fuel Pressure Sensor With Limiter Assy LH'],
                    ['id' => 'RCV-022', 'group' => 'Rear Side View', 'label' => 'Common Rail Assy'],
                    ['id' => 'RCV-023', 'group' => 'Rear Side View', 'label' => 'Boost Pressure Sensor'],
                    ['id' => 'RCV-024', 'group' => 'Rear Side View', 'label' => 'Intake Temperature Sensor'],
                    ['id' => 'RCV-025', 'group' => 'Rear Side View', 'label' => 'Air Intake Manifold LH'],
                    ['id' => 'RCV-026', 'group' => 'Rear Side View', 'label' => 'Exhaust Temperature Sensor (4 pcs) With Bracket'],
                    ['id' => 'RCV-027', 'group' => 'Rear Side View', 'label' => 'Customer Responsibilities Sticker'],
                    ['id' => 'RCV-028', 'group' => 'Rear Side View', 'label' => 'Heater Switch Assy With Cover LH'],
                    ['id' => 'RCV-029', 'group' => 'Rear Side View', 'label' => 'Exhaust Brake Assy LH'],
                    ['id' => 'RCV-030', 'group' => 'Rear Side View', 'label' => 'Bracket Exhaust Brake Mounting'],
                    ['id' => 'RCV-031', 'group' => 'Rear Side View', 'label' => 'Turbocharger Assy RH P/N : 6505-67-5030'],
                    ['id' => 'RCV-032', 'group' => 'Rear Side View', 'label' => 'Heat Tab position'],
                    ['id' => 'RCV-033', 'group' => 'Rear Side View', 'label' => 'Engine Controller Assy LH'],
                    ['id' => 'RCV-034', 'group' => 'Rear Side View', 'label' => 'Caution Flywheel Sticker'],
                    ['id' => 'RCV-035', 'group' => 'Rear Side View', 'label' => 'Pick Up Revolution Sensor'],
                    ['id' => 'RCV-036', 'group' => 'Rear Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-037', 'group' => 'Rear Side View', 'label' => 'Rear Bracket Mounting (2 pcs)'],
                    ['id' => 'RCV-038', 'group' => 'Rear Side View', 'label' => 'Flywheel (P/N : 6219-31-4110)'],
                    ['id' => 'RCV-039', 'group' => 'Rear Side View', 'label' => 'Engine Controller Assy RH'],
                    ['id' => 'RCV-040', 'group' => 'Rear Side View', 'label' => 'Stiker Attention Installation Report'],
                    ['id' => 'RCV-041', 'group' => 'Rear Side View', 'label' => 'Exhaust Manifold Assy RH & LH'],

                    // === Right Side View ===
                    ['id' => 'RCV-042', 'group' => 'Right Side View', 'label' => 'Air Connector RH'],
                    ['id' => 'RCV-043', 'group' => 'Right Side View', 'label' => 'Exhaust Brake Assy RH'],
                    ['id' => 'RCV-044', 'group' => 'Right Side View', 'label' => 'Heater Switch Assy With Cover RH'],
                    ['id' => 'RCV-045', 'group' => 'Right Side View', 'label' => 'Air Intake Manifold RH'],
                    ['id' => 'RCV-046', 'group' => 'Right Side View', 'label' => 'Oil Cooler Assy'],
                    ['id' => 'RCV-047', 'group' => 'Right Side View', 'label' => 'Tube Cooling System Piping'],
                    ['id' => 'RCV-048', 'group' => 'Right Side View', 'label' => 'Fuel Pressure Sensor With Limiter Assy RH'],
                    ['id' => 'RCV-049', 'group' => 'Right Side View', 'label' => 'Common Rail Assy'],
                    ['id' => 'RCV-050', 'group' => 'Right Side View', 'label' => 'Priming Pump Assy With Cover RH'],
                    ['id' => 'RCV-051', 'group' => 'Right Side View', 'label' => 'Fuel Supply Pump Assy RH (P/N : 6219-71-1120)'],
                    ['id' => 'RCV-052', 'group' => 'Right Side View', 'label' => 'Oil Pan'],
                    ['id' => 'RCV-053', 'group' => 'Right Side View', 'label' => 'Water Inlet Cover Plate'],
                    ['id' => 'RCV-054', 'group' => 'Right Side View', 'label' => 'Water Pump Assy'],
                    ['id' => 'RCV-055', 'group' => 'Right Side View', 'label' => 'Fuel Filter Assy'],
                    ['id' => 'RCV-056', 'group' => 'Right Side View', 'label' => 'Cover Safety Guard Alternator'],
                    ['id' => 'RCV-057', 'group' => 'Right Side View', 'label' => 'Tube, Breather With Hose RH'],
                    ['id' => 'RCV-058', 'group' => 'Right Side View', 'label' => 'Oil Filter Assy'],
                    ['id' => 'RCV-059', 'group' => 'Right Side View', 'label' => 'Alternator Assy (90A) (P/N : 600-825-9330)'],
                    ['id' => 'RCV-060', 'group' => 'Right Side View', 'label' => 'Tube Air Intake Connection RH'],

                    // === Front Side View ===
                    ['id' => 'RCV-062', 'group' => 'Front Side View', 'label' => 'Plate Front Hanger'],
                    ['id' => 'RCV-063', 'group' => 'Front Side View', 'label' => 'Fan Drive With Pulley Assy'],
                    ['id' => 'RCV-064', 'group' => 'Front Side View', 'label' => 'Block Fuel Return With Sensor Assy RH & LH'],
                    ['id' => 'RCV-065', 'group' => 'Front Side View', 'label' => 'V-Belt Alternator'],
                    ['id' => 'RCV-066', 'group' => 'Front Side View', 'label' => 'Alternator Drive Pulley'],
                    ['id' => 'RCV-067', 'group' => 'Front Side View', 'label' => 'Tension Pulley Assy'],
                    ['id' => 'RCV-068', 'group' => 'Front Side View', 'label' => 'Vibration Damper Assy (P/N : 6215-31-8200)'],
                    ['id' => 'RCV-069', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-070', 'group' => 'Front Side View', 'label' => 'Crank Pulley Assy'],
                    ['id' => 'RCV-071', 'group' => 'Front Side View', 'label' => 'Pointer'],
                    ['id' => 'RCV-072', 'group' => 'Front Side View', 'label' => 'Yoke'],
                    ['id' => 'RCV-073', 'group' => 'Front Side View', 'label' => 'V-Belt Fan Set'],
                    ['id' => 'RCV-074', 'group' => 'Front Side View', 'label' => 'Blowbay Sensor Assy'],
                    ['id' => 'RCV-075', 'group' => 'Front Side View', 'label' => 'Water Temperature Sensor'],
                    ['id' => 'RCV-076', 'group' => 'Front Side View', 'label' => 'Thermostat Housing'],
                    ['id' => 'RCV-077', 'group' => 'Front Side View', 'label' => 'Tube Water Outlet (2 pcs)'],
                    ['id' => 'RCV-078', 'group' => 'Front Side View', 'label' => 'Cooling Fan'],
                ],
            ]
        );

        // =============================================
        // D155-6
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'D155-6'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [

                    // === Left Side View ===
                    ['id' => 'RCV-001', 'group' => 'Left Side View', 'label' => 'Painting (Y / B) YELLOW'],
                    ['id' => 'RCV-002', 'group' => 'Left Side View', 'label' => 'Corrosion Resistor'],
                    ['id' => 'RCV-003', 'group' => 'Left Side View', 'label' => 'Air Intake Manifold'],
                    ['id' => 'RCV-004', 'group' => 'Left Side View', 'label' => 'Fuel filter assy'],
                    ['id' => 'RCV-005', 'group' => 'Left Side View', 'label' => 'Pre fuel filter'],
                    ['id' => 'RCV-006', 'group' => 'Left Side View', 'label' => 'Fuel pump assy(6261-71-1111)'],
                    ['id' => 'RCV-007', 'group' => 'Left Side View', 'label' => 'Tube breather with hose'],
                    ['id' => 'RCV-008', 'group' => 'Left Side View', 'label' => 'Coommon rail assy'],
                    ['id' => 'RCV-009', 'group' => 'Left Side View', 'label' => 'Priming pump cover'],
                    ['id' => 'RCV-010', 'group' => 'Left Side View', 'label' => 'Oil pan'],
                    ['id' => 'RCV-011', 'group' => 'Left Side View', 'label' => 'Drain Plug With Valve'],
                    ['id' => 'RCV-012', 'group' => 'Left Side View', 'label' => 'Warning Sticker Oil Level'],
                    ['id' => 'RCV-013', 'group' => 'Left Side View', 'label' => 'Oil Filter Assy'],
                    ['id' => 'RCV-014', 'group' => 'Left Side View', 'label' => 'Tube Return Oil'],
                    ['id' => 'RCV-015', 'group' => 'Left Side View', 'label' => 'Oil filler cap'],
                    ['id' => 'RCV-016', 'group' => 'Left Side View', 'label' => 'Oil filter head assy'],
                    ['id' => 'RCV-017', 'group' => 'Left Side View', 'label' => 'Gauge Oil (Oil Pan STD : H+ 0~10 mm)'],
                    ['id' => 'RCV-018', 'group' => 'Left Side View', 'label' => 'ECM assy,P/N:'],

                    // === Front Side View ===
                    ['id' => 'RCV-019', 'group' => 'Front Side View', 'label' => 'Main harness assy'],
                    ['id' => 'RCV-020', 'group' => 'Front Side View', 'label' => 'Muffler assy with cover'],
                    ['id' => 'RCV-021', 'group' => 'Front Side View', 'label' => 'Muffler bracket'],
                    ['id' => 'RCV-022', 'group' => 'Front Side View', 'label' => 'Tube water outlet'],
                    ['id' => 'RCV-023', 'group' => 'Front Side View', 'label' => 'Alternator assy,P/N:'],
                    ['id' => 'RCV-024', 'group' => 'Front Side View', 'label' => 'V belt Alternator'],
                    ['id' => 'RCV-025', 'group' => 'Front Side View', 'label' => 'Alternator drive pulley'],
                    ['id' => 'RCV-026', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-027', 'group' => 'Front Side View', 'label' => 'Front Damper'],
                    ['id' => 'RCV-028', 'group' => 'Front Side View', 'label' => 'PTO Drive'],
                    ['id' => 'RCV-029', 'group' => 'Front Side View', 'label' => 'Front Hanger'],
                    ['id' => 'RCV-030', 'group' => 'Front Side View', 'label' => 'Air connector'],
                    ['id' => 'RCV-031', 'group' => 'Front Side View', 'label' => 'Engine stand (Original/Standard)'],

                    // === Right Side View ===
                    ['id' => 'RCV-032', 'group' => 'Right Side View', 'label' => 'Turbocharger Assy (P/N :6505-68-5540)'],
                    ['id' => 'RCV-033', 'group' => 'Right Side View', 'label' => 'Exhaust Manifold'],
                    ['id' => 'RCV-034', 'group' => 'Right Side View', 'label' => 'Air connector'],
                    ['id' => 'RCV-035', 'group' => 'Right Side View', 'label' => 'Name Plate Engine'],
                    ['id' => 'RCV-036', 'group' => 'Right Side View', 'label' => 'Rear Hanger'],
                    ['id' => 'RCV-037', 'group' => 'Right Side View', 'label' => 'Housing flywheel'],
                    ['id' => 'RCV-038', 'group' => 'Right Side View', 'label' => 'Oil cooler'],
                    ['id' => 'RCV-039', 'group' => 'Right Side View', 'label' => 'Starting Motor Assy (7.5 KW) (P/N : 600-813-3530)'],
                    ['id' => 'RCV-040', 'group' => 'Right Side View', 'label' => 'Tube Drain Turbocharger'],
                    ['id' => 'RCV-041', 'group' => 'Right Side View', 'label' => 'Tube Drain Muffler'],
                    ['id' => 'RCV-042', 'group' => 'Right Side View', 'label' => 'Valve Water Drain Cock'],
                    ['id' => 'RCV-043', 'group' => 'Right Side View', 'label' => 'Water Pump Assy'],
                    ['id' => 'RCV-044', 'group' => 'Right Side View', 'label' => 'Heat shield assy'],
                    ['id' => 'RCV-045', 'group' => 'Right Side View', 'label' => 'Alternator guard'],

                    // === Rear Side View ===
                    ['id' => 'RCV-046', 'group' => 'Rear Side View', 'label' => 'Heat tab'],
                    ['id' => 'RCV-047', 'group' => 'Rear Side View', 'label' => 'Pilot bearing'],
                    ['id' => 'RCV-048', 'group' => 'Rear Side View', 'label' => 'Rear bracket engine'],
                    ['id' => 'RCV-049', 'group' => 'Rear Side View', 'label' => 'Flywheel assy'],
                ],
            ]
        );

        // =============================================
        // WA800-3
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'WA800-3'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [

                    // === Right Side View ===
                    ['id' => 'RCV-001', 'group' => 'Right Side View', 'label' => 'Painting'],
                    ['id' => 'RCV-002', 'group' => 'Right Side View', 'label' => 'Production No.'],
                    ['id' => 'RCV-003', 'group' => 'Right Side View', 'label' => 'Air Crossover Housing'],
                    ['id' => 'RCV-004', 'group' => 'Right Side View', 'label' => 'Turbocharger LH'],
                    ['id' => 'RCV-005', 'group' => 'Right Side View', 'label' => 'Turbocharger RH'],
                    ['id' => 'RCV-006', 'group' => 'Right Side View', 'label' => 'Water outlet connection RH'],
                    ['id' => 'RCV-007', 'group' => 'Right Side View', 'label' => 'Lifting Bracket'],
                    ['id' => 'RCV-008', 'group' => 'Right Side View', 'label' => 'Fan belt idler assembly'],
                    ['id' => 'RCV-009', 'group' => 'Right Side View', 'label' => 'After cooler water inlet tube'],
                    ['id' => 'RCV-010', 'group' => 'Right Side View', 'label' => 'Fan belt idler pulley'],
                    ['id' => 'RCV-011', 'group' => 'Right Side View', 'label' => 'Water pump'],
                    ['id' => 'RCV-012', 'group' => 'Right Side View', 'label' => 'Water inlet connection'],
                    ['id' => 'RCV-013', 'group' => 'Right Side View', 'label' => 'Lubricating oil filler tube'],
                    ['id' => 'RCV-014', 'group' => 'Right Side View', 'label' => 'Fuel lift pump'],
                    ['id' => 'RCV-015', 'group' => 'Right Side View', 'label' => 'Lubricating oil drain RH'],
                    ['id' => 'RCV-016', 'group' => 'Right Side View', 'label' => 'Fuel filters'],
                    ['id' => 'RCV-017', 'group' => 'Right Side View', 'label' => 'Fuel injection pump LH'],

                    // === Left Side View ===
                    ['id' => 'RCV-018', 'group' => 'Left Side View', 'label' => 'Lubricating oil by pass filters'],
                    ['id' => 'RCV-019', 'group' => 'Left Side View', 'label' => 'Lubricating oil transfer tube'],
                    ['id' => 'RCV-020', 'group' => 'Left Side View', 'label' => 'Air intake manifold'],
                    ['id' => 'RCV-021', 'group' => 'Left Side View', 'label' => 'Turbocharger inlet connection'],
                    ['id' => 'RCV-022', 'group' => 'Left Side View', 'label' => 'Turbocharger outlet connection'],
                    ['id' => 'RCV-023', 'group' => 'Left Side View', 'label' => 'After cooler housing'],
                    ['id' => 'RCV-024', 'group' => 'Left Side View', 'label' => 'Cam follower cover'],
                    ['id' => 'RCV-025', 'group' => 'Left Side View', 'label' => 'High pressure fuel supply lines'],
                    ['id' => 'RCV-026', 'group' => 'Left Side View', 'label' => 'Oil pressure sensor'],
                    ['id' => 'RCV-027', 'group' => 'Left Side View', 'label' => 'Starting Motor'],
                    ['id' => 'RCV-028', 'group' => 'Left Side View', 'label' => 'Lubricating oil drain LH'],
                    ['id' => 'RCV-029', 'group' => 'Left Side View', 'label' => 'Fuel lift pump LH'],
                    ['id' => 'RCV-030', 'group' => 'Left Side View', 'label' => 'Fuel Injection pump RH'],
                    ['id' => 'RCV-031', 'group' => 'Left Side View', 'label' => 'Vibration damper'],
                    ['id' => 'RCV-032', 'group' => 'Left Side View', 'label' => 'Fuel pump drive'],
                    ['id' => 'RCV-033', 'group' => 'Left Side View', 'label' => 'Crankcase Breather'],
                    ['id' => 'RCV-034', 'group' => 'Left Side View', 'label' => 'Coolant temperature sensor'],
                    ['id' => 'RCV-035', 'group' => 'Left Side View', 'label' => 'Fan hub'],
                    ['id' => 'RCV-036', 'group' => 'Left Side View', 'label' => 'Thermostat housing'],
                    ['id' => 'RCV-037', 'group' => 'Left Side View', 'label' => 'Water vent tubes'],

                    // === Rear Side View ===
                    ['id' => 'RCV-038', 'group' => 'Rear Side View', 'label' => 'Flywheel housing'],
                    ['id' => 'RCV-039', 'group' => 'Rear Side View', 'label' => 'Flywheel'],
                    ['id' => 'RCV-040', 'group' => 'Rear Side View', 'label' => 'Engine position sensor (Industrial)'],
                    ['id' => 'RCV-041', 'group' => 'Rear Side View', 'label' => 'Engine speed sensor (Industrial)'],
                    ['id' => 'RCV-042', 'group' => 'Rear Side View', 'label' => 'Alternator'],
                    ['id' => 'RCV-043', 'group' => 'Rear Side View', 'label' => 'Fan hub'],
                    ['id' => 'RCV-044', 'group' => 'Rear Side View', 'label' => 'Air crossover'],
                    ['id' => 'RCV-045', 'group' => 'Rear Side View', 'label' => 'Coolant temperature sensor'],
                    ['id' => 'RCV-046', 'group' => 'Rear Side View', 'label' => 'Oil pan'],
                    ['id' => 'RCV-047', 'group' => 'Rear Side View', 'label' => 'Fan idler tensioner pulley'],
                    ['id' => 'RCV-048', 'group' => 'Rear Side View', 'label' => 'Corossion Resistors'],
                    ['id' => 'RCV-049', 'group' => 'Rear Side View', 'label' => 'Accessory drive'],
                    ['id' => 'RCV-050', 'group' => 'Rear Side View', 'label' => 'Full-flow oil filters'],
                    ['id' => 'RCV-051', 'group' => 'Rear Side View', 'label' => 'LH ECM'],

                    // === Front Side View ===
                    ['id' => 'RCV-052', 'group' => 'Front Side View', 'label' => 'RH ECM'],
                    ['id' => 'RCV-053', 'group' => 'Front Side View', 'label' => 'Front PTO'],
                    ['id' => 'RCV-054', 'group' => 'Front Side View', 'label' => 'Air Compressor'],
                    ['id' => 'RCV-055', 'group' => 'Front Side View', 'label' => 'Wiring hardness'],
                    ['id' => 'RCV-056', 'group' => 'Front Side View', 'label' => 'Oil Level'],
                ],
            ]
        );

        // =============================================
        // GD825A-2
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'GD825A-2'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [

                    // === Left Side View ===
                    ['id' => 'RCV-001', 'group' => 'Left Side View', 'label' => 'Painting ( Y / B )'],
                    ['id' => 'RCV-002', 'group' => 'Left Side View', 'label' => 'Bracket Muffler'],
                    ['id' => 'RCV-003', 'group' => 'Left Side View', 'label' => 'Air Intake Manifold'],
                    ['id' => 'RCV-004', 'group' => 'Left Side View', 'label' => 'Gauge Oil (Oil Pan STD : H+ 0~10 mm)'],
                    ['id' => 'RCV-005', 'group' => 'Left Side View', 'label' => 'Tube Breather With Hose'],
                    ['id' => 'RCV-006', 'group' => 'Left Side View', 'label' => 'Air Compressor Assy ( P/N : 6210-81-3113)'],
                    ['id' => 'RCV-007', 'group' => 'Left Side View', 'label' => 'Fuel Injection Pump Drive With Cover'],
                    ['id' => 'RCV-008', 'group' => 'Left Side View', 'label' => 'Feed Pump Assy'],
                    ['id' => 'RCV-009', 'group' => 'Left Side View', 'label' => 'Fuel Injection Pump Assy (P/N : 6211-72-1470)'],
                    ['id' => 'RCV-010', 'group' => 'Left Side View', 'label' => 'Drain Plug With Valve'],
                    ['id' => 'RCV-011', 'group' => 'Left Side View', 'label' => 'Oil Level Sensor'],
                    ['id' => 'RCV-012', 'group' => 'Left Side View', 'label' => 'Oil Filler Cap'],
                    ['id' => 'RCV-013', 'group' => 'Left Side View', 'label' => 'Oil Pressure Sensor'],
                    ['id' => 'RCV-014', 'group' => 'Left Side View', 'label' => 'Tube Return Oil'],
                    ['id' => 'RCV-015', 'group' => 'Left Side View', 'label' => 'Tube Inlet Oil'],
                    ['id' => 'RCV-016', 'group' => 'Left Side View', 'label' => 'Oil Filter Assy'],
                    ['id' => 'RCV-017', 'group' => 'Left Side View', 'label' => 'Fuel Filter Assy'],

                    // === Front Side View ===
                    ['id' => 'RCV-018', 'group' => 'Front Side View', 'label' => 'Unloader Valve'],
                    ['id' => 'RCV-019', 'group' => 'Front Side View', 'label' => 'Relay Heater Assy'],
                    ['id' => 'RCV-020', 'group' => 'Front Side View', 'label' => 'Valve Assy'],
                    ['id' => 'RCV-021', 'group' => 'Front Side View', 'label' => 'Fan Drive With Pulley'],
                    ['id' => 'RCV-022', 'group' => 'Front Side View', 'label' => 'V-Belt Fan'],
                    ['id' => 'RCV-023', 'group' => 'Front Side View', 'label' => 'Tension Pulley Assy'],
                    ['id' => 'RCV-024', 'group' => 'Front Side View', 'label' => 'Vibration Damper Assy (P/N : 6211-32-8300)'],
                    ['id' => 'RCV-025', 'group' => 'Front Side View', 'label' => 'Crank Pulley'],
                    ['id' => 'RCV-026', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-027', 'group' => 'Front Side View', 'label' => 'Pointer'],
                    ['id' => 'RCV-028', 'group' => 'Front Side View', 'label' => 'Accecories Drive Pulley'],
                    ['id' => 'RCV-029', 'group' => 'Front Side View', 'label' => 'Cooling Fan'],
                    ['id' => 'RCV-030', 'group' => 'Front Side View', 'label' => 'RFID Tag, Traceability mounting bolt'],
                    ['id' => 'RCV-031', 'group' => 'Front Side View', 'label' => 'Heat Tab Position'],
                    ['id' => 'RCV-032', 'group' => 'Front Side View', 'label' => 'Customer Responsibilities Sticker'],
                    ['id' => 'RCV-033', 'group' => 'Front Side View', 'label' => 'Warning Sticker Oil Level'],
                    ['id' => 'RCV-034', 'group' => 'Front Side View', 'label' => 'Caution Flywheel sticker'],
                    ['id' => 'RCV-035', 'group' => 'Front Side View', 'label' => 'Caution Tag'],
                    ['id' => 'RCV-036', 'group' => 'Front Side View', 'label' => 'Quality passed Inspection Tag'],
                    ['id' => 'RCV-037', 'group' => 'Front Side View', 'label' => 'Masking Condition'],

                    // === Right Side View ===
                    ['id' => 'RCV-038', 'group' => 'Right Side View', 'label' => 'Turbocharger Assy (P/N : 6505-52-5440)'],
                    ['id' => 'RCV-039', 'group' => 'Right Side View', 'label' => 'Connector Air Cleaner'],
                    ['id' => 'RCV-040', 'group' => 'Right Side View', 'label' => 'Exhaust Manifold'],
                    ['id' => 'RCV-041', 'group' => 'Right Side View', 'label' => 'Oil Cooler'],
                    ['id' => 'RCV-042', 'group' => 'Right Side View', 'label' => 'Name Plate Engine'],
                    ['id' => 'RCV-043', 'group' => 'Right Side View', 'label' => 'Starting Motor Assy (11 KW) (P/N : 600-813-4222)'],
                    ['id' => 'RCV-044', 'group' => 'Right Side View', 'label' => 'Engine Stand (Original / Std)'],
                    ['id' => 'RCV-045', 'group' => 'Right Side View', 'label' => 'Tube Drain Turbocharger'],
                    ['id' => 'RCV-046', 'group' => 'Right Side View', 'label' => 'Tube Drain Muffler'],
                    ['id' => 'RCV-047', 'group' => 'Right Side View', 'label' => 'Oil Pan'],
                    ['id' => 'RCV-048', 'group' => 'Right Side View', 'label' => 'Valve Water Drain Cock'],
                    ['id' => 'RCV-049', 'group' => 'Right Side View', 'label' => 'Water Pump Assy'],
                    ['id' => 'RCV-050', 'group' => 'Right Side View', 'label' => 'Water Inlet Cover Plate'],
                    ['id' => 'RCV-051', 'group' => 'Right Side View', 'label' => 'Alternator Drive Pulley'],
                    ['id' => 'RCV-052', 'group' => 'Right Side View', 'label' => 'Alternator Assy (75 A) (P/N : 600-825-7212)'],
                    ['id' => 'RCV-053', 'group' => 'Right Side View', 'label' => 'Thermostat Housing'],
                    ['id' => 'RCV-054', 'group' => 'Right Side View', 'label' => 'Tube Water Outlet'],
                    ['id' => 'RCV-055', 'group' => 'Right Side View', 'label' => 'Tube Air Vent'],
                    ['id' => 'RCV-056', 'group' => 'Right Side View', 'label' => 'Elbow Exhaust Connector'],
                    ['id' => 'RCV-057', 'group' => 'Right Side View', 'label' => 'Muffler'],

                    // === Rear Side View ===
                    ['id' => 'RCV-058', 'group' => 'Rear Side View', 'label' => 'Pre-Cleaner'],
                    ['id' => 'RCV-059', 'group' => 'Rear Side View', 'label' => 'Air Cleaner'],
                    ['id' => 'RCV-060', 'group' => 'Rear Side View', 'label' => 'Bracket Air Cleaner'],
                    ['id' => 'RCV-061', 'group' => 'Rear Side View', 'label' => 'Dust Indicator'],
                    ['id' => 'RCV-062', 'group' => 'Rear Side View', 'label' => 'Stop Engine Motor'],
                    ['id' => 'RCV-063', 'group' => 'Rear Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-064', 'group' => 'Rear Side View', 'label' => 'Rear Bracket (RH & LH)'],
                    ['id' => 'RCV-065', 'group' => 'Rear Side View', 'label' => 'Flywheel (P/N : 6210-31-1310)'],
                    ['id' => 'RCV-066', 'group' => 'Rear Side View', 'label' => 'Injection Timing :'],
                    ['id' => 'RCV-067', 'group' => 'Rear Side View', 'label' => 'Stiker Attention Installation Report'],
                    ['id' => 'RCV-068', 'group' => 'Rear Side View', 'label' => 'Sticker Information Cleaner'],
                    ['id' => 'RCV-069', 'group' => 'Rear Side View', 'label' => 'Check Mounting Bolt (Rear Bracket & Front Support)'],
                ],
            ]
        );

        // =============================================
        // HD465-7R
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'HD465-7R'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [

                    // === Left Side View ===
                    ['id' => 'RCV-001', 'group' => 'Left Side View', 'label' => 'Painting Y / B'],
                    ['id' => 'RCV-002', 'group' => 'Left Side View', 'label' => 'Common Rail Assy'],
                    ['id' => 'RCV-003', 'group' => 'Left Side View', 'label' => 'Air Connector'],
                    ['id' => 'RCV-004', 'group' => 'Left Side View', 'label' => 'Breather With Hose'],
                    ['id' => 'RCV-005', 'group' => 'Left Side View', 'label' => 'Corrosion Resistor Assy'],
                    ['id' => 'RCV-006', 'group' => 'Left Side View', 'label' => 'Air Intake Connector'],
                    ['id' => 'RCV-007', 'group' => 'Left Side View', 'label' => 'Fuel Pre-Filter Assy'],
                    ['id' => 'RCV-008', 'group' => 'Left Side View', 'label' => 'Air Bleed Valve Assy'],
                    ['id' => 'RCV-009', 'group' => 'Left Side View', 'label' => 'Fuel Filter Assy'],
                    ['id' => 'RCV-010', 'group' => 'Left Side View', 'label' => 'Tube Oil Filler With Cap & Bracket'],
                    ['id' => 'RCV-011', 'group' => 'Left Side View', 'label' => 'Gauge Oil (Large Oil Pan STD : H+ 0~10 mm)'],
                    ['id' => 'RCV-012', 'group' => 'Left Side View', 'label' => 'Oil Level Sensor Assy'],
                    ['id' => 'RCV-013', 'group' => 'Left Side View', 'label' => 'Drain Oil Plug With Valve'],
                    ['id' => 'RCV-014', 'group' => 'Left Side View', 'label' => 'Quality passed Inspection Tag'],
                    ['id' => 'RCV-015', 'group' => 'Left Side View', 'label' => 'Warning Sticker Oil Level'],
                    ['id' => 'RCV-016', 'group' => 'Left Side View', 'label' => 'Caution Tag'],

                    // === Rear Side View ===
                    ['id' => 'RCV-017', 'group' => 'Rear Side View', 'label' => 'Priming Pump Assy & Piping with Bracket'],
                    ['id' => 'RCV-018', 'group' => 'Rear Side View', 'label' => 'Baring Divice Assy'],
                    ['id' => 'RCV-019', 'group' => 'Rear Side View', 'label' => 'Sensor Revolution'],
                    ['id' => 'RCV-020', 'group' => 'Rear Side View', 'label' => 'Engine Controller'],
                    ['id' => 'RCV-021', 'group' => 'Rear Side View', 'label' => 'Fuel Supply Pump Assy (P/N : 6245-71-1110)'],
                    ['id' => 'RCV-022', 'group' => 'Rear Side View', 'label' => 'Feed Pump Assy'],
                    ['id' => 'RCV-023', 'group' => 'Rear Side View', 'label' => 'Boost Pressure Sensor With Temperature Sensor Assy'],
                    ['id' => 'RCV-024', 'group' => 'Rear Side View', 'label' => 'Air Intake Manifold Assy'],
                    ['id' => 'RCV-025', 'group' => 'Rear Side View', 'label' => 'Wiring Harness Assy'],
                    ['id' => 'RCV-026', 'group' => 'Rear Side View', 'label' => 'Plate Flywheel Housing'],
                    ['id' => 'RCV-027', 'group' => 'Rear Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-028', 'group' => 'Rear Side View', 'label' => 'Flywheel (P/N : 6240-31-1910)'],
                    ['id' => 'RCV-029', 'group' => 'Rear Side View', 'label' => 'Rear Bracket (RH & LH)'],
                    ['id' => 'RCV-030', 'group' => 'Rear Side View', 'label' => 'Caution Flywheel sticker'],
                    ['id' => 'RCV-031', 'group' => 'Rear Side View', 'label' => 'Heat Tab position'],
                    ['id' => 'RCV-032', 'group' => 'Rear Side View', 'label' => 'Heat Shield Support'],
                    ['id' => 'RCV-033', 'group' => 'Rear Side View', 'label' => 'All rubber hoses not painting'],
                    ['id' => 'RCV-034', 'group' => 'Rear Side View', 'label' => 'All inside diameter Pulley not painting'],
                    ['id' => 'RCV-035', 'group' => 'Rear Side View', 'label' => 'Check Mounting Bolt (Rear Bracket & Front Support)'],

                    // === Right Side View ===
                    ['id' => 'RCV-036', 'group' => 'Right Side View', 'label' => 'Turbocharger Assy (P/N : 6502-51-5010)'],
                    ['id' => 'RCV-037', 'group' => 'Right Side View', 'label' => 'Heat Shield Assy'],
                    ['id' => 'RCV-038', 'group' => 'Right Side View', 'label' => 'Customer Responsibilities Sticker'],
                    ['id' => 'RCV-039', 'group' => 'Right Side View', 'label' => 'Tube Exhaust'],
                    ['id' => 'RCV-040', 'group' => 'Right Side View', 'label' => 'Exhaust Manifold Assy'],
                    ['id' => 'RCV-041', 'group' => 'Right Side View', 'label' => 'Name Plate Engine'],
                    ['id' => 'RCV-042', 'group' => 'Right Side View', 'label' => 'Oil Cooler Assy (2 pcs)'],
                    ['id' => 'RCV-043', 'group' => 'Right Side View', 'label' => 'Starting Motor Assy (7.5 KW) (P/N : 600-813-7152)'],
                    ['id' => 'RCV-044', 'group' => 'Right Side View', 'label' => 'Tube Drain Turbocharger'],
                    ['id' => 'RCV-045', 'group' => 'Right Side View', 'label' => 'Oil Pan'],
                    ['id' => 'RCV-046', 'group' => 'Right Side View', 'label' => 'Water Pump Assy'],
                    ['id' => 'RCV-047', 'group' => 'Right Side View', 'label' => 'Thermostat Housing'],

                    // === Front Side View ===
                    ['id' => 'RCV-048', 'group' => 'Front Side View', 'label' => 'Stiker Attention Installation Report'],
                    ['id' => 'RCV-049', 'group' => 'Front Side View', 'label' => 'Tube Water Outlet'],
                    ['id' => 'RCV-050', 'group' => 'Front Side View', 'label' => 'Fan Drive With Pulley'],
                    ['id' => 'RCV-051', 'group' => 'Front Side View', 'label' => 'Tension Pulley Assy'],
                    ['id' => 'RCV-052', 'group' => 'Front Side View', 'label' => 'Vibration Damper Assy'],
                    ['id' => 'RCV-053', 'group' => 'Front Side View', 'label' => 'Crank Pulley Assy'],
                    ['id' => 'RCV-054', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-055', 'group' => 'Front Side View', 'label' => 'Alternator Drive Pulley'],
                    ['id' => 'RCV-056', 'group' => 'Front Side View', 'label' => 'AC Compressor Assy'],
                    ['id' => 'RCV-057', 'group' => 'Front Side View', 'label' => 'Tension Assy'],
                    ['id' => 'RCV-058', 'group' => 'Front Side View', 'label' => 'Alternator Assy (90 A) (P/N : 600-861-9120)'],
                    ['id' => 'RCV-059', 'group' => 'Front Side View', 'label' => 'S/N :'],
                    ['id' => 'RCV-060', 'group' => 'Front Side View', 'label' => 'RFID Tag, Traceability mounting bolt'],
                    ['id' => 'RCV-061', 'group' => 'Front Side View', 'label' => 'Front hanger'],
                ],
            ]
        );

        // =============================================
        // PC1250-8
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'PC1250-8'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [

                    // === Right Side View ===
                    ['id' => 'RCV-001', 'group' => 'Right Side View', 'label' => 'Painting ( Y / B )'],
                    ['id' => 'RCV-002', 'group' => 'Right Side View', 'label' => 'Turbocharger ( P/N : 6502-51-5020 )'],
                    ['id' => 'RCV-003', 'group' => 'Right Side View', 'label' => 'Tube Support'],
                    ['id' => 'RCV-004', 'group' => 'Right Side View', 'label' => 'Hose Air Cleaner'],
                    ['id' => 'RCV-005', 'group' => 'Right Side View', 'label' => 'Water Temperature Sensor Assy'],
                    ['id' => 'RCV-006', 'group' => 'Right Side View', 'label' => 'Cover Safety Exhaust Manifold (Heat Shield Assy)'],
                    ['id' => 'RCV-007', 'group' => 'Right Side View', 'label' => 'Name Plate Engine'],
                    ['id' => 'RCV-008', 'group' => 'Right Side View', 'label' => 'Starting Motor ( 11Kw ) ( P/N : 600-813-9511 )'],
                    ['id' => 'RCV-009', 'group' => 'Right Side View', 'label' => 'Oil Cooler (2 pcs)'],
                    ['id' => 'RCV-010', 'group' => 'Right Side View', 'label' => 'Tube Return Turbocharger'],
                    ['id' => 'RCV-011', 'group' => 'Right Side View', 'label' => 'Oil Pan'],
                    ['id' => 'RCV-012', 'group' => 'Right Side View', 'label' => 'Valve Water Drain Cock'],
                    ['id' => 'RCV-013', 'group' => 'Right Side View', 'label' => 'Water Inlet Cover Plate'],
                    ['id' => 'RCV-014', 'group' => 'Right Side View', 'label' => 'Water Pump Assy'],
                    ['id' => 'RCV-015', 'group' => 'Right Side View', 'label' => 'Tube Exhaust Gas EGR Re-Circulation'],
                    ['id' => 'RCV-016', 'group' => 'Right Side View', 'label' => 'Thermostat Housing'],

                    // === Rear Side View ===
                    ['id' => 'RCV-017', 'group' => 'Rear Side View', 'label' => 'Exhaust Manifold Assy'],
                    ['id' => 'RCV-018', 'group' => 'Rear Side View', 'label' => 'Tube Breather With Hose'],
                    ['id' => 'RCV-019', 'group' => 'Rear Side View', 'label' => 'Bracket Tube Breather'],
                    ['id' => 'RCV-020', 'group' => 'Rear Side View', 'label' => 'Exhaust Temperature Sensor'],
                    ['id' => 'RCV-021', 'group' => 'Rear Side View', 'label' => 'Air Cleaner'],
                    ['id' => 'RCV-022', 'group' => 'Rear Side View', 'label' => 'Bracket Air Cleaner'],
                    ['id' => 'RCV-023', 'group' => 'Rear Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-024', 'group' => 'Rear Side View', 'label' => 'Flywheel (P/N : 6245-31-1610)'],
                    ['id' => 'RCV-025', 'group' => 'Rear Side View', 'label' => 'Rear Bracket (2 pcs)'],
                    ['id' => 'RCV-026', 'group' => 'Rear Side View', 'label' => 'Engine Stand (Original / Std)'],
                    ['id' => 'RCV-027', 'group' => 'Rear Side View', 'label' => 'Sensor Revolution Assy'],
                    ['id' => 'RCV-028', 'group' => 'Rear Side View', 'label' => 'Heat Tab Position'],
                    ['id' => 'RCV-029', 'group' => 'Rear Side View', 'label' => 'Customer Responsibilities Sticker'],
                    ['id' => 'RCV-030', 'group' => 'Rear Side View', 'label' => 'Caution Flywheel sticker'],
                    ['id' => 'RCV-031', 'group' => 'Rear Side View', 'label' => 'Caution Tag'],
                    ['id' => 'RCV-032', 'group' => 'Rear Side View', 'label' => 'Warning Sticker Oil Level'],
                    ['id' => 'RCV-033', 'group' => 'Rear Side View', 'label' => 'Quality passed Inspection Tag'],
                    ['id' => 'RCV-034', 'group' => 'Rear Side View', 'label' => 'Masking Condition'],
                    ['id' => 'RCV-035', 'group' => 'Rear Side View', 'label' => 'All rubber hoses not painting'],
                    ['id' => 'RCV-036', 'group' => 'Rear Side View', 'label' => 'All inside diameter pulley not painting'],
                    ['id' => 'RCV-037', 'group' => 'Rear Side View', 'label' => 'Stiker Attention Installation Report'],
                    ['id' => 'RCV-038', 'group' => 'Rear Side View', 'label' => 'RFID Tag, Traceability mounting bolt'],
                    ['id' => 'RCV-039', 'group' => 'Rear Side View', 'label' => 'Stiker Information Cleaner'],
                    ['id' => 'RCV-040', 'group' => 'Rear Side View', 'label' => 'Check Mounting Bolt (Rear Bracket & Front Support)'],

                    // === Left Side View ===
                    ['id' => 'RCV-041', 'group' => 'Left Side View', 'label' => 'Oil Pressure Sensor Assy'],
                    ['id' => 'RCV-042', 'group' => 'Left Side View', 'label' => 'Oil Temperature Sensor Assy'],
                    ['id' => 'RCV-043', 'group' => 'Left Side View', 'label' => 'Oil Filter'],
                    ['id' => 'RCV-044', 'group' => 'Left Side View', 'label' => 'Tube Air'],
                    ['id' => 'RCV-045', 'group' => 'Left Side View', 'label' => 'Air Connector Intake Manifold'],
                    ['id' => 'RCV-046', 'group' => 'Left Side View', 'label' => 'Alternator Assy (90A) (P/N : 600-861-9120)'],
                    ['id' => 'RCV-047', 'group' => 'Left Side View', 'label' => 'Bracket Belt Drive Guard'],
                    ['id' => 'RCV-048', 'group' => 'Left Side View', 'label' => 'AC Compressor Assy'],
                    ['id' => 'RCV-049', 'group' => 'Left Side View', 'label' => 'Air Compressor Assy (P/N : 6210-81-3122)'],
                    ['id' => 'RCV-050', 'group' => 'Left Side View', 'label' => 'Corrosion Resistor'],
                    ['id' => 'RCV-051', 'group' => 'Left Side View', 'label' => 'Blowbay Sensor'],
                    ['id' => 'RCV-052', 'group' => 'Left Side View', 'label' => 'Gauge Oil (STD : H+ 0~10 mm)'],
                    ['id' => 'RCV-053', 'group' => 'Left Side View', 'label' => 'Tube Oil Filer Cap'],
                    ['id' => 'RCV-054', 'group' => 'Left Side View', 'label' => 'Oil Level Sensor'],
                    ['id' => 'RCV-055', 'group' => 'Left Side View', 'label' => 'Drain Plug With Valve'],

                    // === Front Side View ===
                    ['id' => 'RCV-056', 'group' => 'Front Side View', 'label' => 'Priming Pump Assy With Cover'],
                    ['id' => 'RCV-057', 'group' => 'Front Side View', 'label' => 'Tube Inlet'],
                    ['id' => 'RCV-058', 'group' => 'Front Side View', 'label' => 'Tube Outlet'],
                    ['id' => 'RCV-059', 'group' => 'Front Side View', 'label' => 'Engine Controller With Cover'],
                    ['id' => 'RCV-060', 'group' => 'Front Side View', 'label' => 'Fuel Pre Filter'],
                    ['id' => 'RCV-061', 'group' => 'Front Side View', 'label' => 'Fuel Supply Pump Assy (P/N : 6245-71-1100)'],
                    ['id' => 'RCV-062', 'group' => 'Front Side View', 'label' => 'Feed Pump Assy'],
                    ['id' => 'RCV-063', 'group' => 'Front Side View', 'label' => 'Dust Indicator'],
                    ['id' => 'RCV-064', 'group' => 'Front Side View', 'label' => 'Engine Name Plate'],
                    ['id' => 'RCV-065', 'group' => 'Front Side View', 'label' => 'Electrical Wiring Harness'],
                    ['id' => 'RCV-066', 'group' => 'Front Side View', 'label' => 'Air Bleed Valve'],
                    ['id' => 'RCV-067', 'group' => 'Front Side View', 'label' => 'Fuel Filter Assy'],
                    ['id' => 'RCV-068', 'group' => 'Front Side View', 'label' => 'Front Hanger Plate'],
                    ['id' => 'RCV-069', 'group' => 'Front Side View', 'label' => 'Tube Water Outlet'],
                    ['id' => 'RCV-070', 'group' => 'Front Side View', 'label' => 'Cooler Mounting'],
                    ['id' => 'RCV-071', 'group' => 'Front Side View', 'label' => 'Tube Water'],
                    ['id' => 'RCV-072', 'group' => 'Front Side View', 'label' => 'Block Water Housing'],
                    ['id' => 'RCV-073', 'group' => 'Front Side View', 'label' => 'Damper Assy (P/N : 6240-31-8100)'],
                    ['id' => 'RCV-074', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-075', 'group' => 'Front Side View', 'label' => 'Oil Pump Assy'],
                    ['id' => 'RCV-076', 'group' => 'Front Side View', 'label' => 'Alternator Drive Pulley'],
                    ['id' => 'RCV-077', 'group' => 'Front Side View', 'label' => 'Tension Pulley Assy'],
                    ['id' => 'RCV-078', 'group' => 'Front Side View', 'label' => 'V-belt Alternator'],
                ],
            ]
        );

        // =============================================
        // PC2000-8
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'PC2000-8'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => [

                    // === Left Side View ===
                    ['id' => 'RCV-001', 'group' => 'Left Side View', 'label' => 'Painting (Y/B) Yellow'],
                    ['id' => 'RCV-002', 'group' => 'Left Side View', 'label' => 'Tube Air Intake Connection LH'],
                    ['id' => 'RCV-003', 'group' => 'Left Side View', 'label' => 'Air Connector LH'],
                    ['id' => 'RCV-004', 'group' => 'Left Side View', 'label' => 'Fuel Filter Assy LH'],
                    ['id' => 'RCV-005', 'group' => 'Left Side View', 'label' => 'Engine Name Plate,in front cover side'],
                    ['id' => 'RCV-006', 'group' => 'Left Side View', 'label' => 'Tube, Breather With Hose LH'],
                    ['id' => 'RCV-007', 'group' => 'Left Side View', 'label' => 'Fuel Supply Pump Assy LH (P/N : 6219-71-1110)'],
                    ['id' => 'RCV-008', 'group' => 'Left Side View', 'label' => 'Tube Oil Filler Cap'],
                    ['id' => 'RCV-009', 'group' => 'Left Side View', 'label' => 'Gauge Oil (STD : H+ 0~10 mm)'],
                    ['id' => 'RCV-010', 'group' => 'Left Side View', 'label' => 'Engine Stand ( Original / Std )'],
                    ['id' => 'RCV-011', 'group' => 'Left Side View', 'label' => 'Oil Level Sensor Assy'],
                    ['id' => 'RCV-012', 'group' => 'Left Side View', 'label' => 'Drain Oil Plug'],
                    ['id' => 'RCV-013', 'group' => 'Left Side View', 'label' => 'Priming Pump Assy With Cover LH'],
                    ['id' => 'RCV-014', 'group' => 'Left Side View', 'label' => 'Warning Sticker Oil Level'],
                    ['id' => 'RCV-015', 'group' => 'Left Side View', 'label' => 'Bracket Mounting'],
                    ['id' => 'RCV-016', 'group' => 'Left Side View', 'label' => 'Quality passed Inspection Tag'],
                    ['id' => 'RCV-017', 'group' => 'Left Side View', 'label' => 'Oil Temperature Sensor'],
                    ['id' => 'RCV-018', 'group' => 'Left Side View', 'label' => 'Oil Pressure Sensor'],

                    // === Rear Side View ===
                    ['id' => 'RCV-019', 'group' => 'Rear Side View', 'label' => 'Starting Motor Assy (7.5 KW) (P/N : 600-813-7542)'],
                    ['id' => 'RCV-020', 'group' => 'Rear Side View', 'label' => 'Fuel Pressure Sensor With Limiter Assy LH'],
                    ['id' => 'RCV-021', 'group' => 'Rear Side View', 'label' => 'Common Rail Assy'],
                    ['id' => 'RCV-022', 'group' => 'Rear Side View', 'label' => 'Boost Pressure Sensor'],
                    ['id' => 'RCV-023', 'group' => 'Rear Side View', 'label' => 'Intake Temperature Sensor'],
                    ['id' => 'RCV-024', 'group' => 'Rear Side View', 'label' => 'Air Intake Manifold LH'],
                    ['id' => 'RCV-025', 'group' => 'Rear Side View', 'label' => 'Exhaust Temperature Sensor (4 pcs) With Bracket'],
                    ['id' => 'RCV-026', 'group' => 'Rear Side View', 'label' => 'Heater Switch Assy With Cover LH'],
                    ['id' => 'RCV-027', 'group' => 'Rear Side View', 'label' => 'Exhaust Brake Assy LH'],
                    ['id' => 'RCV-028', 'group' => 'Rear Side View', 'label' => 'Bracket Exhaust Brake Mounting'],
                    ['id' => 'RCV-029', 'group' => 'Rear Side View', 'label' => 'Turbocharger Assy RH'],
                    ['id' => 'RCV-030', 'group' => 'Rear Side View', 'label' => 'Heat Tab position'],
                    ['id' => 'RCV-031', 'group' => 'Rear Side View', 'label' => 'Engine Controller Assy LH'],
                    ['id' => 'RCV-032', 'group' => 'Rear Side View', 'label' => 'Caution Flywheel Sticker and Water temperatur sensor'],
                    ['id' => 'RCV-033', 'group' => 'Rear Side View', 'label' => 'Pick Up Revolution Sensor'],
                    ['id' => 'RCV-034', 'group' => 'Rear Side View', 'label' => 'Flywheel Housing'],
                    ['id' => 'RCV-035', 'group' => 'Rear Side View', 'label' => 'Rear Bracket Mounting (2 pcs)'],
                    ['id' => 'RCV-036', 'group' => 'Rear Side View', 'label' => 'Flywheel (P/N : 6219-31-4110)'],
                    ['id' => 'RCV-037', 'group' => 'Rear Side View', 'label' => 'Engine Controller Assy RH'],
                    ['id' => 'RCV-038', 'group' => 'Rear Side View', 'label' => 'Exhaust Manifold Assy RH & LH'],

                    // === Right Side View ===
                    ['id' => 'RCV-039', 'group' => 'Right Side View', 'label' => 'Air Connector RH'],
                    ['id' => 'RCV-040', 'group' => 'Right Side View', 'label' => 'Exhaust Brake Assy RH'],
                    ['id' => 'RCV-041', 'group' => 'Right Side View', 'label' => 'Heater Switch Assy With Cover RH'],
                    ['id' => 'RCV-042', 'group' => 'Right Side View', 'label' => 'Air Intake Manifold RH'],
                    ['id' => 'RCV-043', 'group' => 'Right Side View', 'label' => 'Oil Cooler Assy'],
                    ['id' => 'RCV-044', 'group' => 'Right Side View', 'label' => 'Bearing divice'],
                    ['id' => 'RCV-045', 'group' => 'Right Side View', 'label' => 'Fuel Pressure Sensor With Limiter Assy RH'],
                    ['id' => 'RCV-046', 'group' => 'Right Side View', 'label' => 'Common Rail Assy'],
                    ['id' => 'RCV-047', 'group' => 'Right Side View', 'label' => 'Priming Pump Assy With Cover RH'],
                    ['id' => 'RCV-048', 'group' => 'Right Side View', 'label' => 'Fuel Supply Pump Assy RH (P/N : 6219-71-1120)'],
                    ['id' => 'RCV-049', 'group' => 'Right Side View', 'label' => 'Oil Pan'],
                    ['id' => 'RCV-050', 'group' => 'Right Side View', 'label' => 'Water Inlet Cover Plate'],
                    ['id' => 'RCV-051', 'group' => 'Right Side View', 'label' => 'Water Pump Assy'],
                    ['id' => 'RCV-052', 'group' => 'Right Side View', 'label' => 'Fuel Filter Assy'],
                    ['id' => 'RCV-053', 'group' => 'Right Side View', 'label' => 'Cover Safety Guard Alternator'],
                    ['id' => 'RCV-054', 'group' => 'Right Side View', 'label' => 'Tube, Breather With Hose RH'],
                    ['id' => 'RCV-055', 'group' => 'Right Side View', 'label' => 'Oil Filter Assy'],
                    ['id' => 'RCV-056', 'group' => 'Right Side View', 'label' => 'Alternator Assy (90A) (P/N : 600-825-9330) 2 pcs'],

                    // === Front Side View ===
                    ['id' => 'RCV-057', 'group' => 'Front Side View', 'label' => 'Tube Air Intake Connection RH'],
                    ['id' => 'RCV-058', 'group' => 'Front Side View', 'label' => 'Plate Front Hanger'],
                    ['id' => 'RCV-059', 'group' => 'Front Side View', 'label' => 'Fan Drive With Pulley Assy'],
                    ['id' => 'RCV-060', 'group' => 'Front Side View', 'label' => 'Block Fuel Return With Sensor Assy RH & LH'],
                    ['id' => 'RCV-061', 'group' => 'Front Side View', 'label' => 'V-Belt Alternator'],
                    ['id' => 'RCV-062', 'group' => 'Front Side View', 'label' => 'Alternator Drive Pulley'],
                    ['id' => 'RCV-063', 'group' => 'Front Side View', 'label' => 'Alternator guard front side'],
                    ['id' => 'RCV-064', 'group' => 'Front Side View', 'label' => 'Vibration Damper Assy (P/N : 6215-31-8200)'],
                    ['id' => 'RCV-065', 'group' => 'Front Side View', 'label' => 'Front Support'],
                    ['id' => 'RCV-066', 'group' => 'Front Side View', 'label' => 'Crank Pulley Assy'],
                    ['id' => 'RCV-067', 'group' => 'Front Side View', 'label' => 'Pointer'],
                    ['id' => 'RCV-068', 'group' => 'Front Side View', 'label' => 'Yoke'],
                    ['id' => 'RCV-069', 'group' => 'Front Side View', 'label' => 'V-Belt Fan Set'],
                    ['id' => 'RCV-070', 'group' => 'Front Side View', 'label' => 'Blowbay Sensor Assy'],
                    ['id' => 'RCV-071', 'group' => 'Front Side View', 'label' => 'Water Temperature Sensor'],
                    ['id' => 'RCV-072', 'group' => 'Front Side View', 'label' => 'Thermostat Housing'],
                    ['id' => 'RCV-073', 'group' => 'Front Side View', 'label' => 'Tube Water Outlet (2 pcs)'],
                    ['id' => 'RCV-074', 'group' => 'Front Side View', 'label' => 'Cooling Fan'],
                    ['id' => 'RCV-075', 'group' => 'Front Side View', 'label' => 'Masking condition'],
                    ['id' => 'RCV-076', 'group' => 'Front Side View', 'label' => 'Check Mounting Bolt (Rear Bracket & Front Support)'],
                ],
            ]
        );

    }
}
