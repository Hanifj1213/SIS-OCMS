<?php

namespace Database\Seeders;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;
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
                    // The source sheet intentionally skips item number 61.
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
                    ['id' => 'RCV-079', 'group' => 'Front Side View', 'label' => 'RFID Tag, Traceability mounting bolt'],
                    ['id' => 'RCV-080', 'group' => 'Front Side View', 'label' => 'Masking condition'],
                    ['id' => 'RCV-081', 'group' => 'Front Side View', 'label' => 'Check Mounting Bolt (Rear Bracket & Front Support) (Std Tightening Torque : 25 kgm)'],
                ],
            ]
        );

        // Pastikan setiap item Engine menampilkan halaman referensi view yang
        // sama dengan pembagian item pada checksheet sumber.
        $withEngineViews = static function (array $items, array $viewEndItems): array {
            foreach ($items as &$item) {
                $itemNumber = (int) substr($item['id'], 4);

                foreach ($viewEndItems as $lastItem => $view) {
                    if ($itemNumber <= $lastItem) {
                        $item['group'] = $view;
                        break;
                    }
                }
            }
            unset($item);

            return $items;
        };

        // =============================================
        // D155-6
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'D155-6'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => $withEngineViews([

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

                    ['id' => 'RCV-019', 'group' => 'Left Side View', 'label' => 'Main harness assy'],
                    ['id' => 'RCV-020', 'group' => 'Left Side View', 'label' => 'Muffler assy with cover'],
                    ['id' => 'RCV-021', 'group' => 'Left Side View', 'label' => 'Muffler bracket'],

                    // === Front Side View ===
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
                ], [
                    21 => 'Left Side View',
                    31 => 'Front Side View',
                    45 => 'Right Side View',
                    49 => 'Rear Side View',
                ]),
            ]
        );

        // =============================================
        // WA800-3
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'WA800-3'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => $withEngineViews([

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
                    ['id' => 'RCV-040', 'group' => 'Rear Side View', 'label' => 'Engine position sensor (Industrial) / Engine speed sensor (G.drive, gen set)'],
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
                ], [
                    17 => 'Right Side View',
                    37 => 'Left Side View',
                    52 => 'Rear Side View',
                    56 => 'Front Side View',
                ]),
            ]
        );

        // =============================================
        // GD825A-2
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'GD825A-2'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => $withEngineViews([

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
                ], [
                    20 => 'Left Side View',
                    37 => 'Front Side View',
                    57 => 'Right Side View',
                    69 => 'Rear Side View',
                ]),
            ]
        );

        // =============================================
        // HD465-7R
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'HD465-7R'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => $withEngineViews([

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
                ], [
                    24 => 'Left Side View',
                    35 => 'Rear Side View',
                    49 => 'Right Side View',
                    61 => 'Front Side View',
                ]),
            ]
        );

        // =============================================
        // PC1250-8
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'PC1250-8'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => $withEngineViews([

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
                ], [
                    20 => 'Right Side View',
                    40 => 'Rear Side View',
                    67 => 'Left Side View',
                    78 => 'Front Side View',
                ]),
            ]
        );

        // =============================================
        // PC2000-8
        // =============================================
        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 1, 'egi_model' => 'PC2000-8'],
            [
                'template_name' => 'Engine Receiving Inspection Sheet',
                'items' => $withEngineViews([

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
                ], [
                    28 => 'Left Side View',
                    38 => 'Rear Side View',
                    57 => 'Right Side View',
                    76 => 'Front Side View',
                ]),
            ]
        );

        // =============================================
        // POWERTRAIN - Stage 1: Receiving Inspection
        // Setiap item di bawah ditranskripsi berurutan dari kolom ITEM
        // pada gambar checksheet asli, termasuk baris tanpa nomor.
        // =============================================
        $makePowertrainItems = static function (string $prefix, array $labels): array {
            return array_map(
                static fn (string $label, int $index): array => [
                    'id' => sprintf('%s-%03d', $prefix, $index + 1),
                    'group' => 'Visual Inspection',
                    'label' => $label,
                ],
                $labels,
                array_keys($labels)
            );
        };

        $powertrainTemplates = [
            [
                'category' => 'TC/Transmission',
                'egi' => 'HD785-7',
                'name' => 'TC/Transmission Receiving Inspection',
                'prefix' => 'PTR',
                'labels' => [
                    'Torque Converter', 'Model', 'Serial Number', 'WO.no', 'T/C Valve', 'Lock Up Valve',
                    'Transmission', 'Model', 'Serial Number', 'WO.no', 'Oil Filter ECMV', 'Breather',
                    'ECMV Cover', 'Connector & Wiring Harness', 'Rear Bracket LH,RH', 'Drain Valve Oil',
                    'Lubricating Valve', 'Oil Pan', 'T/M Housing', 'Bolt Connection', 'Temperature Sensor',
                    'T/M name plate', 'Intermediate Shaft Speed Sensor', 'Front bracket',
                    'Out put shaft Speed Sensor', 'T/C name plate', 'T/C Housing', 'PTO Spline',
                    'Front Coupling', 'Rear coupling', 'Eye Bolt', 'Cover', 'Stand ( Original/STD )',
                    'Painting', 'Packing',
                ],
            ],
            [
                'category' => 'TC/Transmission',
                'egi' => 'D155-6',
                'name' => 'TC/Transmission Receiving Inspection',
                'prefix' => 'PTR',
                'labels' => [
                    'PTO', 'Torque Converter', 'Transmission', 'Steering Brake RH & LH', 'Sudden Brake Valve',
                    'ECMV Brake', 'Centralized Pick Up Port', 'Piping & Hose Suction Pump', 'Front Support',
                    'Piping Supply', 'Piping Lubrication PTO', 'Power Train Pump', 'Block Divider', 'Steering ECMV',
                    'Main Relief', 'Scavenging Pump Assy', 'Scavenging piping', 'Lift Hanger (3pcs)',
                    'Filter With Element Assy Steering Brake', 'Revolution Sensor', 'Coupling',
                    'Warning (Sticker)', 'Painting condition', 'Packing / wraping', 'Stand', 'Bolt stand',
                    'Name plate', 'Comp.S/N',
                ],
            ],
            [
                'category' => 'TC/Transmission',
                'egi' => 'D375-6',
                'name' => 'TC/Transmission Receiving Inspection',
                'prefix' => 'PTR',
                'labels' => [
                    'Torque Converter', 'Model', 'Serial Number', 'Production Number', 'Scavenging pump',
                    'Power Train Pump mounting', 'Steering Lub pump', 'Work Equipment pump', 'PTO', 'Coupling',
                    'T/C Valve', 'Transmission', 'Model', 'Serial Number', 'Production Number',
                    'Transmission Case', 'T/M ECMV Valve', 'Bevel Gear & Transver Case',
                    'Steering & Brake RH + LH', 'Steering & Brake ECMV', 'Power train lubrication oil filter',
                    'Power train oil filter', 'RPM/ Speed Sensor', 'Power Train Strainer', 'Drain Plug',
                    'Centraized Press. detection port', 'Sudden Brake prevention Valve', 'Parking Brake Lever',
                    'Level gauge oil', 'Oil feeler port', 'Birther', 'Front Support', 'Painting',
                    'Steel Skid (stand)', 'Packing',
                ],
            ],
            [
                'category' => 'TC/Transmission',
                'egi' => 'GD825A-2',
                'name' => 'TC/Transmission Receiving Inspection',
                'prefix' => 'PTR',
                'labels' => [
                    'Brither', 'Transmission Control Valve', 'Transmission', 'Model', 'Serial Number',
                    'Production Number', 'PTO', 'Drai Plug', 'Strainer',
                ],
            ],
            [
                'category' => 'TC/Transmission',
                'egi' => 'HD1500-7',
                'name' => 'TC/Transmission Receiving Inspection',
                'prefix' => 'PTR',
                'labels' => [
                    'Torque Converter', 'Oil Filter Assy', 'Wiring Harness', 'Lubricating Valve Assy', 'Oil Pan',
                    'Magnetic Strainer with Cover', 'Drain Oil Plug', 'Revolution Sensor (Front)',
                    'Revolution Sensor (Intermediate)', 'ECMV Assy (8 pcs)', 'T/C Regulator Valve',
                    'Main Relief Valve', 'Lock Up Accumulator', 'Lock Up Accumulator Piping', 'Breather',
                    'Eye Bolt Front (2 pcs)', 'Front Coupling', 'Bolt Mounting Coupling', 'Plate', 'PTO (3pcs)',
                    'Eye Bolt Rear (2pcs)', 'Name Plate Original', 'Rear Coupling',
                    'Bolt Mounting Coupling Rear', 'Plate', 'Painting', 'Stand', 'Masking', 'Sticker Oil Level',
                ],
            ],
            [
                'category' => 'TC/Transmission',
                'egi' => 'WA800-3',
                'name' => 'TC/Transmission Receiving Inspection',
                'prefix' => 'PTR',
                'labels' => [
                    'Torque Converter Assy', 'Transmission Assy', 'Transfer Gear Assy', 'Cap Oil Filler',
                    'Tube Oil Filler', 'Rear Output Coupling', 'Plate Retainer', 'Bolt', 'Magnetic Strainer',
                    'Bolt Coupling', 'Input Coupling', 'Plate Retainer', 'PTO (3 gears)', 'Eye Rear Front (2pcs)',
                    'Cover Bearing', 'Cover Idle Gear', 'Plate Retainer', 'Bolt Coupling',
                    'Front Output Coupling', 'Hose Levelling', 'Drain Valve', 'Torque Converter Valve', 'Filter',
                    'Main Relief', 'Breather', 'Transmission C/V Assy', 'Eye Bolt Front (2pcs)',
                    'Painting condition', 'Packing / wraping', 'Stand', 'Bolt stand', 'Warning (Sticker)',
                    'Name plate', 'Comp. S/N',
                ],
            ],
            [
                'category' => 'Final Drive',
                'egi' => 'HD785-7',
                'name' => 'Final Drive Receiving Inspection',
                'prefix' => 'PFD',
                'labels' => [
                    'Comp. Model', 'Aplication', 'Serial Number', 'Wo No.', 'Sun Gear (19 teeth)',
                    'Planetary gear (31 teeth)', 'Planetary gear Shaft', 'Ring Gear (87 teeth)', 'Inner hub',
                    'Wheel Hub', 'Drive shaft', 'Reservoir tank', 'Cylinder', 'Gear', 'Stud bolt',
                    'Cover and monting bolt', 'Stand', 'Painting', 'Packing',
                ],
            ],
            [
                'category' => 'Final Drive',
                'egi' => 'D155-6',
                'name' => 'Final Drive Receiving Inspection',
                'prefix' => 'PFD',
                'labels' => [
                    'Hole protection', 'Bolt cover', 'Retainer', 'Sprocket', 'Cover', 'Rock Destroyer', 'Guard',
                    'Bolt Guard', 'Bolt Sprocket', 'Name plate', 'COMP. S/N', 'Warning(Sticker)', 'Plug',
                    'Warning (Sticker)', 'Painting condition', 'Packing / wraping', 'Stand', 'Bolt stand',
                ],
            ],
            [
                'category' => 'Final Drive',
                'egi' => 'D375-6',
                'name' => 'Final Drive Receiving Inspection',
                'prefix' => 'PFD',
                'labels' => [
                    'Comp. Model', 'Aplication', 'Serial Number', 'Production No.', 'Floating Seal',
                    'Sun Gear (16 teeth)', 'Carrier', 'Hub', 'Cover', 'Sprocket Boss', 'Sprocket teeth',
                    'Floating Seal Guard', 'Cover', 'Planetary Pinion (26 teeth)', 'Ring Gear (68 teeth)',
                    'Cover', 'No. 1 gear (79 teeth)', 'No. 1 gear Hub', 'No. 1 Pinion (20 teeth)',
                    'Final Drive Case', 'Bearing Cage', 'Boss', 'Shaft', 'Wear Guard', 'Pivot Shaft',
                ],
            ],
            [
                'category' => 'Final Drive',
                'egi' => 'GD825A-2',
                'name' => 'Final Drive Receiving Inspection',
                'prefix' => 'PFD',
                'labels' => [
                    'Shaft RH', 'Sprocket RH', 'Side case RH', 'Center case', 'Cover', 'Coupling',
                    'Side case LH', 'Sprocket LH', 'Shaft LH', 'Case', 'Cover', 'Tube', 'Tube',
                    'Stand', 'Painting', 'Packing',
                ],
            ],
            [
                'category' => 'Final Drive',
                'egi' => 'PC1250-8',
                'name' => 'Final Drive Receiving Inspection',
                'prefix' => 'PFD',
                'labels' => [
                    'Final Drive', 'Model', 'Serial Number', 'Production No.', 'Level Plug', 'Drain Plug',
                    'Cover', 'No. 2 Planetary Carrier', 'No. 2 Sun Gear', 'Drive Gear',
                    'No. 1 Planetary Carrier', 'No. 2 Planetary Gear', 'Hub', 'Sprocket', 'Floating Seal',
                    'Case', 'Coupling', 'No. 1 Sun Gear', 'Travel Motor', 'Idler Gear', 'No. Ring Gear',
                    'No1. Planetary Gear', 'Driven Gear', 'No. 2 Ring Gear',
                ],
            ],
            [
                'category' => 'Final Drive',
                'egi' => 'PC2000-8',
                'name' => 'Final Drive Receiving Inspection',
                'prefix' => 'PFD',
                'labels' => [
                    'Final Drive', 'Model', 'Serial Number', 'Production No.', 'No. 2 Planetary carrier',
                    'No.2 ring gear(No.of teeth:69)', 'Floating seal', 'Housing', 'Sprocket', 'Hub', 'Housing',
                    'No.1 Ring gear No.of teeth:80', 'No.1 Planetary gear (No.of teeth : 33)',
                    'Drive gear (No.of teeth : 12)', 'Travel motor case', 'Travel motor',
                    'No.1 sun gear (No.of teeth:13)', 'Driven gear (No.of teeth :67)', 'Case',
                    'No.1 planetary carrier', 'No. 2 planetary gear (No.of teeth :26)',
                    'No.2 Sun gear (No.of teeth : 15)',
                ],
            ],
            [
                'category' => 'Differential',
                'egi' => 'HD785-7',
                'name' => 'Differential Receiving Inspection',
                'prefix' => 'PDF',
                'labels' => [
                    'Differential', 'Model', 'Serial Number', 'Wo No.', 'Case', 'Cage', 'Coupling', 'Cage',
                    'Bevel gear', 'Nut', 'Cap', 'Bolt mounting bevel gear', 'Difflock', 'Stand', 'Painting',
                    'Packing',
                ],
            ],
            [
                'category' => 'PTO',
                'egi' => 'PC1250-8',
                'name' => 'PTO Receiving Inspection',
                'prefix' => 'PTO',
                'labels' => [
                    'Front Case', 'Breather', 'Oil dipstick', 'Plug', 'Valve assy', 'Center of HVP Shaft',
                    'Center of Shaft', 'Center of HVP Shaft', 'Center of HVP Shaft', 'Bolt monting case',
                    'Elbow', 'Rear case', 'Packing', 'Stand', 'Painting',
                ],
            ],
            [
                'category' => 'PTO',
                'egi' => 'PC2000-8',
                'name' => 'PTO Receiving Inspection',
                'prefix' => 'PTO',
                'labels' => [
                    'Power Take Off', 'Model', 'Serial Number', 'WO No', 'Driven gear teeth 57', 'Coupling',
                    'Main Shaft', 'Conection plate', 'PTO Case', 'Driven gear teeth 72,60',
                    'Driven gear teeth 56', 'Breather', 'A - Centre of HVP 375+375 Shaft',
                    'B - Centre of HVP 375+375 Shaft', 'C - Centre fan pump 95+95 Shaft',
                    'D - Centre input Shaft',
                ],
            ],
            [
                'category' => 'Swing Machinery',
                'egi' => 'PC1250-8',
                'name' => 'Swing Machinery Receiving Inspection',
                'prefix' => 'SWM',
                'labels' => [
                    'Case', 'Cover', 'Bolt monting Gear', 'Gear', 'Drain valve', 'Cover', 'Shaft', 'Packing',
                    'Painting', 'Stand',
                ],
            ],
            [
                'category' => 'Swing Machinery',
                'egi' => 'PC2000-8',
                'name' => 'Swing Machinery Receiving Inspection',
                'prefix' => 'SWM',
                'labels' => [
                    'Pinion Gear (Masked)', 'Main Case', 'Top Cover', 'Bracket Mounting Tube', 'Tube', 'Hose',
                    'Breather', 'Drain Valve with Hose', 'Warning (Sticker)', 'Name plate', 'Painting condition',
                    'Packing / wraping', 'Stand', 'Bolt stand',
                ],
            ],
            [
                'category' => 'Control Valve',
                'egi' => 'PC1250-8',
                'name' => 'Control Valve Receiving Inspection',
                'prefix' => 'CVL',
                'labels' => [
                    'Spool (5pcs)', 'Main Relief Valve', 'Suction Valve', 'Suction Safety Valve (5pcs)',
                    'Plug (4pcs)', 'Jet Sensor Relief Valve', 'Cover (5pcs)', 'Name Plate',
                    'Painting condition', 'Packing / wraping', 'Stand', 'Bolt stand',
                ],
            ],
            [
                'category' => 'Control Valve',
                'egi' => 'PC2000-8',
                'name' => 'Control Valve Receiving Inspection',
                'prefix' => 'CVL',
                'labels' => [
                    'Spool & Cover (5pcs)', 'Safety Suction Valve', '2 Stage Safety Suction Valve', 'Main Relief',
                    'Spool & Cover (5pcs)', 'Plug (7pcs)', 'Spool & Cover (5pcs)', 'Plug',
                    'Spool & Cover (5pcs)', 'Safety Suction Valve', 'Sensor to Controller', 'Valve Block',
                    'Flange', 'Sensor to Controller', 'Painting condition', 'Packing / wraping', 'Stand',
                    'Bolt stand',
                ],
            ],
            // EGI Control Valve dari folder COMPLETED — item + group diisi
            // dari database/data/control_valve_receiving_items.json (lihat bawah).
            [
                'category' => 'Control Valve',
                'egi' => 'D155-6',
                'name' => 'Control Valve Receiving Inspection',
                'prefix' => 'CVL',
                'labels' => [],
                'from_json' => true,
            ],
            [
                'category' => 'Control Valve',
                'egi' => 'D375-6',
                'name' => 'Control Valve Receiving Inspection',
                'prefix' => 'CVL',
                'labels' => [],
                'from_json' => true,
            ],
            [
                'category' => 'Control Valve',
                'egi' => 'HD785-7',
                'name' => 'Control Valve Receiving Inspection',
                'prefix' => 'CVL',
                'labels' => [],
                'from_json' => true,
            ],
            [
                'category' => 'Control Valve',
                'egi' => 'GD825A-2',
                'name' => 'Control Valve Receiving Inspection',
                'prefix' => 'CVL',
                'labels' => [],
                'from_json' => true,
            ],
            [
                'category' => 'Control Valve',
                'egi' => 'WA800-3',
                'name' => 'Control Valve Receiving Inspection',
                'prefix' => 'CVL',
                'labels' => [],
                'from_json' => true,
            ],
            [
                'category' => 'Hydraulic Cylinder',
                'egi' => 'HD785-7',
                'name' => 'Cylinder Hoist Receiving Inspection',
                'prefix' => 'CYL',
                'labels' => [
                    'Cylinder Tube', 'Rod', 'Gland', 'Piston', 'Seal Kit condition',
                    'Eye / Clevis', 'Name Plate', 'Painting condition', 'Packing / wraping', 'Stand',
                ],
            ],
            [
                'category' => 'Front Suspension',
                'egi' => 'HD785-7',
                'name' => 'Front Suspension Receiving Inspection',
                'prefix' => 'FSP',
                'labels' => [
                    'Cylinder / Housing', 'Rod', 'Mounting', 'Seal condition',
                    'Name Plate', 'Painting condition', 'Packing / wraping', 'Stand',
                ],
            ],
            [
                'category' => 'Rear Suspension',
                'egi' => 'HD785-7',
                'name' => 'Rear Suspension Receiving Inspection',
                'prefix' => 'RSP',
                'labels' => [
                    'Cylinder / Housing', 'Rod', 'Mounting', 'Seal condition',
                    'Name Plate', 'Painting condition', 'Packing / wraping', 'Stand',
                ],
            ],
        ];

        $cvReceivingJsonPath = database_path('data/control_valve_receiving_items.json');
        $cvReceivingByEgi = [];
        if (is_file($cvReceivingJsonPath)) {
            $cvReceivingByEgi = json_decode((string) file_get_contents($cvReceivingJsonPath), true) ?: [];
        }

        foreach ($powertrainTemplates as $template) {
            $items = $makePowertrainItems($template['prefix'], $template['labels']);

            // Control Valve D155/D375/... : item + group + callout number
            // dari sheet RECEIVING asli (mirip Engine view + nomor callout).
            if (! empty($template['from_json'])) {
                $jsonItems = $cvReceivingByEgi[$template['egi']] ?? [];
                if ($jsonItems !== []) {
                    $items = array_map(static function (array $row) use ($template): array {
                        $item = [
                            'id' => $row['id'] ?? sprintf('%s-%03d', $template['prefix'], 0),
                            'group' => $row['group'] ?? 'Visual Inspection',
                            'label' => $row['label'] ?? '',
                        ];
                        if (isset($row['number'])) {
                            $item['number'] = (int) $row['number'];
                        }

                        return $item;
                    }, $jsonItems);
                }
            }

            if ($items === []) {
                continue;
            }

            ChecksheetTemplate::updateOrCreate(
                [
                    'major_category' => $template['category'],
                    'stage_number' => 1,
                    'egi_model' => $template['egi'],
                ],
                [
                    'template_name' => $template['name'],
                    'items' => $items,
                ]
            );
        }

        // =============================================
        // ENGINE D375-6 — Stage 2: DIS ASSEMBLING,
        // CLEANING & MEASUREMENT
        //
        // The source folder contains several related SOP sheets.  They are
        // kept in one stage snapshot, but each source sheet remains a
        // separate group so the slider/list view follows the original
        // document structure.
        // =============================================
        $stage2Items = [];
        $addStage2Item = static function (string $id, string $group, string $label, ?string $standard = null, ?string $source = null) use (&$stage2Items): void {
            $item = [
                'id' => $id,
                'group' => $group,
                'label' => $label,
            ];

            if ($standard !== null && $standard !== '') {
                $item['standard'] = $standard;
            }
            if ($source !== null && $source !== '') {
                $item['source'] = $source;
            }

            $stage2Items[] = $item;
        };

        $disassembly = 'Disassembly Check Sheet';
        $disassemblyRows = [
            ['Engine ass\'y', 'Setting engine to engine stand; prepare a stable stand; drain engine oil; rotate the crankshaft engine 720° to check whether the engine is jammed.', 'p.6'],
            ['Starter Motor / Alternator', 'Note if either component is not fitted to the engine; record starting motor and alternator serial numbers.', 'p.6'],
            ['Turbocharger', 'Remove turbocharger, mounting bolts and adapters; remove turbo oil-line fittings and check pipes and connections.', 'p.6'],
            ['Exhaust Manifold', 'Remove and inspect for cracks, pulled threads, snapped studs and missing bolts.', 'p.6'],
            ['Air Intake Connector', 'Remove air intake connector assembly and check for cracked or damaged condition.', 'p.6'],
            ['Water Pump / Thermostat', 'Remove tube and water pump assembly; check leakage, wear and damage; remove thermostat assembly.', 'p.6'],
            ['Oil Cooler', 'Check oil cooler housing for cracks and pulled threads; check cooler for dents and leaks.', 'p.6'],
            ['Fuel Filter Ass\'y', 'Remove tube and fuel filter assembly; check pulled threads, cracks and damage.', 'p.6'],
            ['Air Intake Manifold', 'Remove fuel inlet tube, timing rail tube and fuel rail tube; inspect manifold, aftercooler and water tubes for cracks, damage, leaks, corrosion and missing bolts.', 'p.7'],
            ['ECM', 'Remove fuel piping and ECM; record fuel pump S/N, ECM P/N, S/N and ESN.', 'p.7'],
            ['Oil Pan', 'Remove oil filler tube, oil pressure sensor, pan and drain plug; inspect brackets, suction pipe and oil pick-up screen for cracks, dents, pulled threads and damage.', 'p.7'],
            ['Head Cover', 'Check for cracks and damage to the seal face.', 'p.7'],
            ['Rocker Arm and Shaft', 'Remove rocker arms and shafts; inspect contact area on crossheads, pushrods and adjusting screws for wear, bends, cracks and damage.', 'p.7'],
            ['Rocker Arm Housing', 'Remove rocker arm housing; inspect for cracked, pulled threads, wear and damage. Fit tags and keep sets for each cylinder number.', 'p.7'],
            ['Injector Ass\'y', 'Remove holder mounting bolts using the special tool; check O-ring contact area, filter clogs and damage.', 'p.7'],
            ['Cylinder Head', 'Remove cylinder head; inspect bolt pin-punch marks, exhaust face and bolt holes, head surface water marks/damage; perform colour-check inspection with QA Officer.', 'p.8'],
            ['Cam Followers', 'Inspect cam followers for damage to the roller contact surface and check cover condition.', 'p.8'],
            ['Vibration Damper and Pulleys', 'Remove and check vibration damper; inspect pulleys for damage/wear, belt tensioner wear and front trunnion mount.', 'p.8'],
            ['Flywheel', 'Remove flywheel; check bolts and bolt holes, pilot bearing hole, and ring gear for chipped or worn teeth.', 'p.8'],
            ['Under Plate', 'Remove under plate and check for dents or damage.', 'p.8'],
            ['Flywheel Housing', 'Remove flywheel housing and check housing for cracks and bolt holes.', 'p.8'],
            ['Front Support and Front Cover', 'Remove and inspect front support for damage; check seal running area condition on the front shaft.', 'p.8'],
            ['Front Gear Ass\'y', 'Record individual gear condition; check chipped teeth, cracks, excessive backlash and end play; inspect shafts, bearings and thrust plate thickness.', 'p.8'],
            ['Oil Pump Assembly', 'Remove oil pump assembly; inspect inlet/outlet O-rings and pump for wear and damage.', 'p.8'],
            ['Camshaft', 'Check camshaft end float, journals for wear, cam surfaces for wear/damage, and camshaft for bends.', 'p.9'],
            ['Rear Idler Gear Ass\'y', 'Check gear backlash and end play; remove idler gear assembly and inspect bushing, shaft, gear teeth chipping and wear.', 'p.9'],
            ['Piston Cooling Nozzles', 'Check nozzles for bends, cracks and blockages in grooves and skirts.', 'p.9'],
            ['Connecting Rods', 'Check rod for cracks/damage, big-end bore scratches or distortion, cap mating face damage, and measure end play.', 'p.9'],
            ['Pistons', 'Measure wear on piston skirt, ring grooves and pin bore.', 'p.9'],
            ['Crankshaft', 'Inspect main and pin journals for scratches, wear, cracks and discoloration; check oil holes, fillet area and journal diameter; mark engine S/N on the rear surface.', 'p.9'],
            ['Main Cap', 'Check cap bolts and cap fit to block; punch S/N on main bearing caps to match engine block; inspect thrust-metal seat and perform colour-check with QA Officer.', 'p.9'],
            ['Cylinder Liners', 'Check fitting, outer cracks, inner scoring/corrosion, seal-groove pitting; measure inside diameter, roundness and cylindricity.', 'p.10'],
            ['Cylinder Block', 'Check block cracks, lower liner-seal area, blind-plug corrosion, threads, cylinder-head mounting surface, oil/water-hole clogging; perform colour-check with QA Officer.', 'p.10'],
        ];
        foreach ($disassemblyRows as $index => [$label, $standard, $sourcePage]) {
            $addStage2Item('DIS-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $disassembly, $label, $standard, 'D375-6 EG MAINLINE.pdf '.$sourcePage);
        }

        $timingGear = 'Disassembly Timing Gear and Reference Measurements';
        $timingGearRows = [
            ['Front timing gear position A', 'Crankshaft gear and idle gear (medium); backlash 0.114-0.320 mm (R/L 0.5).'],
            ['Front timing gear position B', 'Crankshaft gear and idle gear (large); backlash 0.134-0.362 mm (R/L 0.5).'],
            ['Front timing gear position C', 'Idle gear (medium) and fuel pump drive gear; backlash 0.114-0.320 mm (R/L 0.5).'],
            ['Front timing gear position D', 'Idle gear (large) and water pump drive gear; backlash 0.121-0.333 mm (R/L 0.5).'],
            ['Front timing gear position E', 'Idle gear (large) and oil pump drive gear; backlash 0.121-0.333 mm (R/L 0.5).'],
            ['End play - oil pump drive gear', '0.04-0.18 mm (C/L 0.34).'],
            ['End play - water pump drive gear', '0.04-0.20 mm (C/L 0.34).'],
            ['End play - idle gear', '0.04-0.17 mm (C/L 0.30).'],
            ['End play - crankshaft gear', '0.04-0.17 mm (C/L 0.30).'],
            ['End play - second idle gear', '0.04-0.17 mm (C/L 0.34).'],
            ['End play - alternator fuel pump drive gear', '0.04-0.17 mm (C/L 0.34).'],
            ['Rear timing gear position A', 'Crankshaft gear and idle gear (large); backlash 0.155-0.412 mm (R/L 0.6).'],
            ['Rear timing gear position B', 'Crankshaft gear and idle gear (medium); backlash 0.145-0.380 mm (R/L 0.6).'],
            ['Rear end play - camshaft drive gear', '0.05-0.20 mm (C/L 0.30).'],
            ['Rear end play - idle gear', '0.04-0.17 mm (C/L 0.34).'],
            ['Rear end play - crankshaft drive gear', '0.04-0.17 mm (C/L 0.30).'],
            ['D170 outside diameter reference', 'Main journal standard 140.00 mm / repair limit 139.91 mm; pin journal standard 108.00 mm / repair limit 107.91 mm; out-of-roundness 0-0.010 mm.'],
            ['D170 cam height and journal reference', 'Intake cam height 62 +0.4144/-0.2144 mm (R.L. 61.37); exhaust 61 +0.3215/-0.5215 mm (R.L. 59.64); camshaft journal STD 72 -0.080/-0.110 mm.'],
        ];
        foreach ($timingGearRows as $index => [$label, $standard]) {
            $addStage2Item('DGT-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $timingGear, $label, $standard, 'D375-6 EG MAINLINE.pdf p.11-13');
        }

        $pistonMeasurement = 'Piston, Piston Ring and Piston Pin Measurement';
        $addStage2Item('PST-001', $pistonMeasurement, 'Outside diameter of piston', 'Standard size 170 mm at right angle from boss; tolerance -0.135/-0.165 mm; repair limit 169.79 mm.', 'piston 170.pdf p.1 / PISTON CHECKSHEET2.pdf p.1');
        $addStage2Item('PST-002', $pistonMeasurement, 'Piston ring groove', 'Top ring and 2nd ring: Keystone; oil ring standard 4.00 mm with tolerance +0.05/+0.03 and -0.01/-0.03; clearance limit 0.15/0.30 mm.', 'piston 170.pdf p.1');
        $addStage2Item('PST-003', $pistonMeasurement, 'Gap in piston ring at end gap', 'Top ring 0.50-0.65 mm; 2nd ring 0.70-0.85 mm; oil ring 0.50-0.70 mm; clearance limit 1.8 mm.', 'piston 170.pdf p.1');
        $addStage2Item('PST-004', $pistonMeasurement, 'Inside diameter of piston pin hole', 'Standard clearance 68 mm; clearance limit +0.044/+0.034 mm.', 'piston 170.pdf p.1 / PISTON CHECKSHEET2.pdf p.1');
        $addStage2Item('PST-005', $pistonMeasurement, 'Outside diameter of piston pin', 'Standard 68 mm; clearance limit 0/-0.006 mm.', 'piston 170.pdf p.1');
        foreach (range(1, 6) as $cylinder) {
            $addStage2Item('PST-'.str_pad((string) (5 + (($cylinder - 1) * 2) + 1), 3, '0', STR_PAD_LEFT), $pistonMeasurement, 'Cylinder '.$cylinder.' piston outside diameter X-X\'', 'Record measurement and clearance actual.', 'PISTON CHECKSHEET2.pdf p.1');
            $addStage2Item('PST-'.str_pad((string) (5 + (($cylinder - 1) * 2) + 2), 3, '0', STR_PAD_LEFT), $pistonMeasurement, 'Cylinder '.$cylinder.' piston outside diameter Y-Y\'', 'Record measurement and clearance actual.', 'PISTON CHECKSHEET2.pdf p.1');
        }
        foreach (range(1, 6) as $cylinder) {
            foreach (['Top ring', '2nd ring', 'Oil ring'] as $ringIndex => $ring) {
                $id = 18 + (($cylinder - 1) * 3) + $ringIndex + 1;
                $addStage2Item('PST-'.str_pad((string) $id, 3, '0', STR_PAD_LEFT), $pistonMeasurement, 'Cylinder '.$cylinder.' '.$ring.' measurement', 'Record ring measurement and clearance actual.', 'PISTON CHECKSHEET2.pdf p.2');
            }
        }
        foreach (range(1, 6) as $cylinder) {
            $addStage2Item('PST-'.str_pad((string) (37 + (($cylinder - 1) * 2) + 1), 3, '0', STR_PAD_LEFT), $pistonMeasurement, 'Cylinder '.$cylinder.' piston pin hole X-X\'', 'Record inside diameter and clearance actual.', 'PISTON CHECKSHEET2.pdf p.2');
            $addStage2Item('PST-'.str_pad((string) (37 + (($cylinder - 1) * 2) + 2), 3, '0', STR_PAD_LEFT), $pistonMeasurement, 'Cylinder '.$cylinder.' piston pin hole Y-Y\'', 'Record inside diameter and clearance actual.', 'PISTON CHECKSHEET2.pdf p.2');
        }

        $pistonPin = 'Piston Pin Measuring and Polishing';
        $addStage2Item('PPM-001', $pistonPin, 'Piston pin standard and clearance limit', '6D170E-5 Ø68.000–67.994 mm; clearance limit 0.11 mm.', 'D375-6 EG MAINLINE.pdf p.15');
        foreach (range(1, 6) as $cylinder) {
            $addStage2Item('PPM-'.str_pad((string) (2 + (($cylinder - 1) * 2)), 3, '0', STR_PAD_LEFT), $pistonPin, 'Cylinder '.$cylinder.' piston pin position X-X\'', 'Record measurement and clearance actual.', 'D375-6 EG MAINLINE.pdf p.15');
            $addStage2Item('PPM-'.str_pad((string) (3 + (($cylinder - 1) * 2)), 3, '0', STR_PAD_LEFT), $pistonPin, 'Cylinder '.$cylinder.' piston pin position Y-Y\'', 'Record measurement and clearance actual.', 'D375-6 EG MAINLINE.pdf p.15');
        }

        $camshaft = 'Camshaft Process and Measurement';
        $addStage2Item('CAM-001', $camshaft, '6D170-5 intake cam lobe', 'Standard 78.065–78.336 mm; repair limit 78.00 mm.', 'D375-6 EG MAINLINE.pdf p.17');
        $addStage2Item('CAM-002', $camshaft, '6D170-5 exhaust cam lobe', 'Standard 77.074–77.355 mm; repair limit 77.00 mm.', 'D375-6 EG MAINLINE.pdf p.17');
        $addStage2Item('CAM-003', $camshaft, '6D170-5 cam journal', 'Standard 89.987–90.035 mm; repair limit 89.98 mm.', 'D375-6 EG MAINLINE.pdf p.17');
        foreach (range(1, 7) as $journal) {
            $addStage2Item('CAM-'.str_pad((string) (3 + $journal), 3, '0', STR_PAD_LEFT), $camshaft, 'Camshaft journal No. '.$journal.' (X / Y)', 'Record journal, intake-lobe and exhaust-lobe measurements.', 'D375-6 EG MAINLINE.pdf p.17');
        }
        $addStage2Item('CAM-011', $camshaft, 'Crack has been checked', 'Mark OK or Not OK after visual inspection.', 'D375-6 EG MAINLINE.pdf p.17');

        $crankMeasure = 'Crankshaft Disassembly and Measurement';
        foreach ([['001', 'Crankshaft', 'Physical data'], ['002', 'Gear', 'Physical data'], ['003', 'Key', 'Physical data'], ['004', 'Dowel Pin', 'Physical data']] as [$id, $label, $standard]) {
            $addStage2Item('CKM-'.$id, $crankMeasure, $label, 'Record quantity and Good / No Good condition.', 'D375-6 EG MAINLINE.pdf p.19');
        }
        $addStage2Item('CKM-005', $crankMeasure, 'Main journal standard size', 'STD 140.000–139.975 mm; under size 0.25: 139.750–139.725; under size 0.50: 139.500–139.475; roundness 0–0.010; fillet radius 6.0–6.5 mm.', 'D375-6 EG MAINLINE.pdf p.19');
        $addStage2Item('CKM-006', $crankMeasure, 'Pin journal standard size', 'STD 108.000–107.978 mm; under size 0.25: 107.750–107.728; under size 0.50: 107.500–107.478; roundness 0–0.010; fillet radius 6.0–6.5 mm.', 'D375-6 EG MAINLINE.pdf p.19');
        $addStage2Item('CKM-007', $crankMeasure, 'Crack, scratch and wear visual check', 'Mark OK or Not OK.', 'D375-6 EG MAINLINE.pdf p.19');
        foreach (range(1, 7) as $journal) {
            $addStage2Item('CKM-'.str_pad((string) (7 + $journal), 3, '0', STR_PAD_LEFT), $crankMeasure, 'Main journal critical inspection point '.$journal, 'Record X-X\', Y-Y\' and roundness.', 'D375-6 EG MAINLINE.pdf p.19');
        }
        foreach (range(1, 6) as $journal) {
            $addStage2Item('CKM-'.str_pad((string) (14 + $journal), 3, '0', STR_PAD_LEFT), $crankMeasure, 'Pin journal critical inspection point '.$journal, 'Record X-X\', Y-Y\' and roundness.', 'D375-6 EG MAINLINE.pdf p.19');
        }
        $addStage2Item('CKM-021', $crankMeasure, 'Critical fillet radius - main journal', 'Record actual measurement.', 'D375-6 EG MAINLINE.pdf p.19');
        $addStage2Item('CKM-022', $crankMeasure, 'Critical fillet radius - pin journal', 'Record actual measurement.', 'D375-6 EG MAINLINE.pdf p.19');

        $conRod = 'Connecting Rod Salvaging and Inspection';
        foreach ([
            'Connecting rod', 'Connecting rod cap', 'Connecting rod bolt', 'Washer', 'Crank pin metal',
        ] as $index => $part) {
            $addStage2Item('CON-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $conRod, $part, 'Record physical data and New / Reuse condition.', 'D375-6 EG MAINLINE.pdf p.22');
        }
        $addStage2Item('CON-006', $conRod, 'Connecting rod crack visual check', 'Mark OK or Not OK.', 'D375-6 EG MAINLINE.pdf p.22');
        $addStage2Item('CON-007', $conRod, 'Standard tightening bolt step', '6D170-5: 19.0–21.0 kgm + 90°–105°.', 'D375-6 EG MAINLINE.pdf p.22');
        $addStage2Item('CON-008', $conRod, 'Standard big-end diameter without bearing', '75.000–75.030 mm; repair limit 75.09 mm.', 'D375-6 EG MAINLINE.pdf p.22');
        $addStage2Item('CON-009', $conRod, 'Standard small-end diameter without bearing', '68.030–68.049 mm.', 'D375-6 EG MAINLINE.pdf p.22');
        $addStage2Item('CON-010', $conRod, 'Connecting rod bend limit', '6D170-5 bend R.L. 0.10 mm.', 'D375-6 EG MAINLINE.pdf p.22');
        $addStage2Item('CON-011', $conRod, 'Connecting rod twist limit', '6D170-5 twist R.L. 0.25 mm.', 'D375-6 EG MAINLINE.pdf p.22');
        $addStage2Item('CON-012', $conRod, 'Distance between holes', '6D170-5: 305 mm.', 'D375-6 EG MAINLINE.pdf p.22');
        foreach (range(1, 6) as $rod) {
            $addStage2Item('CON-'.str_pad((string) (12 + $rod), 3, '0', STR_PAD_LEFT), $conRod, 'Small end connecting rod '.$rod.' (X-X\' / Y-Y\')', 'Record diameter after salvaging with new bearing.', 'D375-6 EG MAINLINE.pdf p.21');
            $addStage2Item('CON-'.str_pad((string) (18 + $rod), 3, '0', STR_PAD_LEFT), $conRod, 'Big end connecting rod '.$rod.' (X-X\' / Y-Y\' / Z-Z\')', 'Record diameter after salvaging with new bearing.', 'D375-6 EG MAINLINE.pdf p.22');
            $addStage2Item('CON-'.str_pad((string) (24 + $rod), 3, '0', STR_PAD_LEFT), $conRod, 'Connecting rod '.$rod.' bend and twist', 'Record indicator reading and actual bend/twist.', 'D375-6 EG MAINLINE.pdf p.21');
        }

        $cylinderBlock = 'Cylinder Block Measuring and Inspection';
        $blockStandards = [
            ['CBM-001', 'Main bearing bore', '147.999–148.025 mm (A).'],
            ['CBM-002', 'Cylinder block height', '488.960–489.040 mm (B).'],
            ['CBM-003', 'Use over-size gasket', '488.560–488.960 mm.'],
            ['CBM-004', 'Counter bore depth', '14.000–14.050 mm (C).'],
            ['CBM-005', 'Cylinder liner O-ring land', '190.340–190.400 mm (D).'],
            ['CBM-006', 'Cylinder liner flange seat', '205.965–206.015 mm (E).'],
            ['CBM-007', 'Counter bore diameter', '194.565–194.615 mm (F).'],
        ];
        foreach ($blockStandards as [$id, $label, $standard]) {
            $addStage2Item($id, $cylinderBlock, $label, $standard, 'D375-6 EG MAINLINE.pdf p.23');
        }
        $addStage2Item('CBM-008', $cylinderBlock, 'Main bearing cap tightening torque', '1st step 27.5–30.5 kgm; 2nd step 57.0–59.0 kgm; 3rd step 90°–120°.', 'D375-6 EG MAINLINE.pdf p.23');
        $addStage2Item('CBM-009', $cylinderBlock, 'Crack visual check', 'Mark Good Condition or Bad Condition.', 'D375-6 EG MAINLINE.pdf p.23');
        foreach (range(1, 7) as $bore) {
            $addStage2Item('CBM-'.str_pad((string) (9 + $bore), 3, '0', STR_PAD_LEFT), $cylinderBlock, 'Main bore No. '.$bore.' measurement (X-X\' / Y-Y\')', 'Record inspection measurement.', 'D375-6 EG MAINLINE.pdf p.23');
        }
        foreach (range(1, 6) as $cylinder) {
            $addStage2Item('CBM-'.str_pad((string) (16 + $cylinder), 3, '0', STR_PAD_LEFT), $cylinderBlock, 'Cylinder '.$cylinder.' liner O-ring land visual check', 'Record mechanic and QA check.', 'D375-6 EG MAINLINE.pdf p.23');
            $addStage2Item('CBM-'.str_pad((string) (22 + $cylinder), 3, '0', STR_PAD_LEFT), $cylinderBlock, 'Cylinder '.$cylinder.' liner flange seat (X-X\' / Y-Y\')', 'Record inspection measurement.', 'D375-6 EG MAINLINE.pdf p.24');
            $addStage2Item('CBM-'.str_pad((string) (28 + $cylinder), 3, '0', STR_PAD_LEFT), $cylinderBlock, 'Cylinder '.$cylinder.' counter bore diameter (X-X\' / Y-Y\')', 'Record inspection measurement.', 'D375-6 EG MAINLINE.pdf p.24');
            $addStage2Item('CBM-'.str_pad((string) (34 + $cylinder), 3, '0', STR_PAD_LEFT), $cylinderBlock, 'Cylinder '.$cylinder.' counter bore depth (X / X\' / Y / Y\')', 'Record after-repair inspection measurement.', 'D375-6 EG MAINLINE.pdf p.25');
        }
        $addStage2Item('CBM-041', $cylinderBlock, 'Roughness surface of counter bore', 'Inspect scratches, rust and wear; record roughness measurement.', 'D375-6 EG MAINLINE.pdf p.25');
        $addStage2Item('CBM-042', $cylinderBlock, 'Flatness of top surface of cylinder block', 'Inspect head-gasket mounting surface for fretting, corrosion and scratches.', 'D375-6 EG MAINLINE.pdf p.25');
        $addStage2Item('CBM-043', $cylinderBlock, 'Flatness cylinder block surface procedure', 'Less than 0.1 mm: recondition with oil stone; less than 0.4 mm: resurface top deck; more than 0.4 mm: replace block.', 'D375-6 EG MAINLINE.pdf p.25');

        $blockInspection = 'Cylinder Block Inspection (ENG-CB-6D170/3-2-001)';
        $blockInspectionRows = [
            'Counter bore surface area', 'Water jacket wall area', 'Cylinder block upper surface', 'O ring liner area',
            'Thread condition - cam follower side', 'Thread condition - cylinder head/top surface LH cylinder block', 'Thread condition - common rail side cylinder block',
            'Timing gear hole area', 'Oil pump contact area', 'Thrust bearing surface', 'Main bore front surface', 'Oil and water hole gallery surface area',
            'Blind cover water area', 'Camshaft hole surface rear area RH/LH', 'Remove all plug on oil and water gallery',
            'All thread condition', 'Oil cooler thread mounting', 'Oil cooler element area', 'Oil drain turbo and water adapter hole',
            'Oil cooler hole front and rear (4 pcs)', 'Check all thread on RH side', 'Upper surface area', 'O ring liner area and chamfer condition',
            'Water jacket area', 'Counter bore area',
        ];
        foreach ($blockInspectionRows as $index => $label) {
            $addStage2Item('CBI-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $blockInspection, $label, 'Mark Good Condition, Bad Condition or None and add remark.', 'D375-6 EG MAINLINE.pdf p.26');
        }

        $damper = 'Front Damper Inspection';
        $damperRows = [
            'Check damper assy is spec for engine 6D170 series.', 'Check front damper assy from bend, chipped, pitted and dirty.',
            'Wash damper using washing chemical to remove oil from damper body.', 'Remove painting from damper body using special paint remover.',
            'Prepare tool assy (bearing heater) for check damper condition.', 'Setting block for damper stand and use beam upon block.',
            'Setting thermometer for check heat temperature; maximum test temperature is 90°C.', 'Setting time on heater machine; time for test damper is 30 minutes.',
            'Put heat sensor machine to damper body; contact area is no damper area.', 'Put temperature sensor of thermometer tool on damper mounting area.',
            'After finish test, check with developer on damper contact cover (front side/part number side) for abnormal leakage.',
            'Before release the damper, check no leakage on the contact cover.', 'Remove damper, clean developer and deliver damper to assembly line.',
        ];
        foreach ($damperRows as $index => $label) {
            $addStage2Item('DMP-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $damper, $label, null, 'D375-6 EG MAINLINE.pdf p.28');
        }

        $crankInspection = 'Crankshaft Inspection';
        $crankInspectionRows = [
            'Fillet radius main and pin journal No.1 from pitting and corrosion',
            'Fillet radius main and pin journal No.2 from pitting and corrosion',
            'Fillet radius main and pin journal No.3 from pitting and corrosion',
            'Fillet radius main and pin journal No.4 from pitting and corrosion',
            'Fillet radius main and pin journal No.5 from pitting and corrosion',
            'Fillet radius main and pin journal No.6 from pitting and corrosion',
            'Fillet radius main journal No.7 from pitting and corrosion',
            'Check condition front and rear seal contact area from wear and fretting',
            'Check crankgear condition from pitting and chipping',
            'Check condition web and counter weight from chipping and pitting',
            'Check condition all thread crankshaft front and rear; mark engine S/N on rear surface area',
        ];
        foreach ($crankInspectionRows as $index => $label) {
            $addStage2Item('CKI-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $crankInspection, $label, null, 'D375-6 EG MAINLINE.pdf p.29');
        }

        $cylinderHead = 'Cylinder Head Before Machining and Measurement';
        $addStage2Item('CHB-001', $cylinderHead, 'Head height', 'Standard 151.05–150.95 mm; repair limit 150.65 mm.', 'D375-6 EG SUBASSY.pdf p.2');
        $addStage2Item('CHB-002', $cylinderHead, 'Lower surface distortion', 'Maximum 0.05 mm; repair limit 0.10 mm.', 'D375-6 EG SUBASSY.pdf p.2');
        $addStage2Item('CHB-003', $cylinderHead, 'O.D. of cross head guide', '13.039–13.028 mm; repair limit 12.999 mm and less.', 'D375-6 EG SUBASSY.pdf p.2');
        $addStage2Item('CHB-004', $cylinderHead, 'Valve seat insert bore (IN / EX)', 'Record standard or oversize bore measurement.', 'D375-6 EG SUBASSY.pdf p.2');
        $addStage2Item('CHB-005', $cylinderHead, 'Valve sink', 'Standard 0; repair limit 0.80 mm.', 'D375-6 EG SUBASSY.pdf p.2');
        $addStage2Item('CHB-006', $cylinderHead, 'Nozzle protrusion', 'Standard 2.30–2.90 mm; replace nozzle sleeve if outside limit.', 'D375-6 EG SUBASSY.pdf p.2');
        $addStage2Item('CHB-007', $cylinderHead, 'Cylinder head measurement result - cylinders 1 to 6', 'Record head height, distortion, cross-head guide and production code for each cylinder head.', 'D375-6 EG SUBASSY.pdf p.2');
        $addStage2Item('CHB-008', $cylinderHead, 'Leak test using pressurized air method in hot water', 'Mark result for each cylinder head.', 'D375-6 EG SUBASSY.pdf p.5');
        $addStage2Item('CHB-009', $cylinderHead, 'Vacuum test', 'Standard: IN; EX 6–8 bar.', 'D375-6 EG SUBASSY.pdf p.5');
        $addStage2Item('CHB-010', $cylinderHead, 'Production code', 'Record production code.', 'D375-6 EG SUBASSY.pdf p.5');

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Engine', 'stage_number' => 2, 'egi_model' => 'D375-6'],
            [
                'template_name' => 'Engine DIS Assembling & Measurement Checksheet — D375-6',
                'items' => $stage2Items,
            ]
        );

        // Backfill the new Stage 2 snapshot for an existing D375-6 engine
        // that has already reached this stage.  Filled snapshots are kept
        // untouched; only missing or still-empty snapshots are refreshed.
        Component::query()
            ->where('major_category', 'Engine')
            ->whereRaw('UPPER(egi) = ?', ['D375-6'])
            ->where('current_stage', '>=', 2)
            ->get()
            ->each(function (Component $component) use ($stage2Items): void {
                $checksheet = ComponentChecksheet::firstOrNew([
                    'comp_id' => $component->comp_id,
                    'stage_number' => 2,
                ]);

                if ($checksheet->exists && $checksheet->completed_at) {
                    return;
                }

                if ($checksheet->exists && ! empty($checksheet->answers)) {
                    $existingIds = collect($checksheet->items ?? [])->pluck('id')->all();
                    $missingItems = collect($stage2Items)
                        ->reject(fn (array $item): bool => in_array($item['id'], $existingIds, true))
                        ->values()
                        ->all();

                    if ($missingItems !== []) {
                        $checksheet->items = array_merge($checksheet->items ?? [], $missingItems);
                        $checksheet->save();
                    }

                    return;
                }

                $checksheet->items = $stage2Items;
                $checksheet->answers = [];
                $checksheet->save();
            });

        $spreadsheetPath = base_path('excel_data_final.json');
        if (file_exists($spreadsheetPath)) {
            $spreadsheetItems = json_decode(file_get_contents($spreadsheetPath), true);
            ChecksheetTemplate::updateOrCreate(
                ['major_category' => 'Engine', 'stage_number' => 2, 'egi_model' => 'PC2000-8'],
                [
                    'template_name' => 'Engine DIS Assembling Spreadsheet — PC2000-8',
                    'items' => $spreadsheetItems,
                ]
            );
        }

        // =============================================
        // GENERIC FALLBACKS FOR POWERTRAIN
        // =============================================

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'TC/Transmission', 'stage_number' => 1, 'egi_model' => null],
            [
                'template_name' => 'TC/Transmission Receiving Inspection (Generic)',
                'items' => [
                    ['id' => 'PTR-001', 'group' => 'Visual Inspection', 'label' => 'Painting condition'],
                    ['id' => 'PTR-002', 'group' => 'Visual Inspection', 'label' => 'Nameplate information & serial number'],
                    ['id' => 'PTR-003', 'group' => 'Visual Inspection', 'label' => 'Housing assembly (no crack/damage)'],
                    ['id' => 'PTR-011', 'group' => 'Fluids & Plugs', 'label' => 'Oil level / condition'],
                    ['id' => 'PTR-014', 'group' => 'Fluids & Plugs', 'label' => 'Check for oil leakage'],
                ],
            ]
        );

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Final Drive', 'stage_number' => 1, 'egi_model' => null],
            [
                'template_name' => 'Final Drive Receiving Inspection (Generic)',
                'items' => [
                    ['id' => 'PFD-001', 'group' => 'Visual Inspection', 'label' => 'Painting condition'],
                    ['id' => 'PFD-003', 'group' => 'Housing & Covers', 'label' => 'Main housing (no crack)'],
                    ['id' => 'PFD-005', 'group' => 'Housing & Covers', 'label' => 'Floating seal area / leak check'],
                    ['id' => 'PFD-008', 'group' => 'Fluids & Plugs', 'label' => 'Oil level / condition'],
                ],
            ]
        );

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Differential', 'stage_number' => 1, 'egi_model' => null],
            [
                'template_name' => 'Differential Receiving Inspection (Generic)',
                'items' => [
                    ['id' => 'PDF-001', 'group' => 'Visual Inspection', 'label' => 'Painting condition'],
                    ['id' => 'PDF-002', 'group' => 'Visual Inspection', 'label' => 'Housing assembly (no crack/damage)'],
                    ['id' => 'PDF-006', 'group' => 'Fluids & Plugs', 'label' => 'Drain plug'],
                ],
            ]
        );

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'PTO', 'stage_number' => 1, 'egi_model' => null],
            [
                'template_name' => 'PTO Receiving Inspection (Generic)',
                'items' => [
                    ['id' => 'PTO-001', 'group' => 'Visual Inspection', 'label' => 'Painting & Nameplate'],
                    ['id' => 'PTO-002', 'group' => 'Visual Inspection', 'label' => 'PTO Case / Housing'],
                    ['id' => 'PTO-006', 'group' => 'Fluids & Plugs', 'label' => 'Oil drain plug / check metal particles'],
                ],
            ]
        );

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Swing Machinery', 'stage_number' => 1, 'egi_model' => null],
            [
                'template_name' => 'Swing Machinery Receiving Inspection (Generic)',
                'items' => [
                    ['id' => 'SWM-001', 'group' => 'Visual Inspection', 'label' => 'Painting & Nameplate'],
                    ['id' => 'SWM-002', 'group' => 'Visual Inspection', 'label' => 'Swing machinery housing'],
                    ['id' => 'SWM-006', 'group' => 'Fluids & Plugs', 'label' => 'Oil level gauge / dipstick'],
                ],
            ]
        );

        ChecksheetTemplate::updateOrCreate(
            ['major_category' => 'Control Valve', 'stage_number' => 1, 'egi_model' => null],
            [
                'template_name' => 'Control Valve Receiving Inspection (Generic)',
                'items' => [
                    ['id' => 'CVL-001', 'group' => 'Visual Inspection', 'label' => 'Painting & Nameplate'],
                    ['id' => 'CVL-002', 'group' => 'Visual Inspection', 'label' => 'Valve block / body (no crack/damage)'],
                    ['id' => 'CVL-003', 'group' => 'Components', 'label' => 'Spool caps & covers'],
                ],
            ]
        );

        // Refresh hanya snapshot Receiving yang belum pernah diisi. Snapshot
        // yang sudah memiliki jawaban harus tetap utuh untuk menjaga jejak audit.
        Component::query()
            ->where('current_stage', 1)
            ->get()
            ->each(function (Component $component): void {
                $egi = strtoupper(trim((string) $component->egi));
                $template = ChecksheetTemplate::query()
                    ->where('major_category', $component->major_category)
                    ->where('stage_number', 1)
                    ->whereRaw('UPPER(egi_model) = ?', [$egi])
                    ->first();

                $template ??= ChecksheetTemplate::query()
                    ->where('major_category', $component->major_category)
                    ->where('stage_number', 1)
                    ->whereNull('egi_model')
                    ->first();

                if (! $template) {
                    return;
                }

                $checksheet = ComponentChecksheet::firstOrNew([
                    'comp_id' => $component->comp_id,
                    'stage_number' => 1,
                ]);

                if ($checksheet->exists && ($checksheet->completed_at || ! empty($checksheet->answers))) {
                    return;
                }

                $checksheet->items = $template->items;
                $checksheet->answers = [];
                $checksheet->save();
            });
    }
}
