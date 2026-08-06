<?php

/*
 * Jam operasional bengkel untuk perhitungan Work Hour.
 *
 * Bengkel beroperasi 07:30-16:30. Jendela di bawah adalah rentang waktu
 * HARIAN saat bengkel TUTUP — rentang ini dipotong dari Calendar Hour
 * untuk menghasilkan Work Hour.
 *
 * Format 'HH:MM'-'HH:MM', tidak boleh melewati tengah malam (pecah jadi
 * dua entri bila perlu, mis. 23:00-24:00 dan 00:00-01:00).
 */
return [
    'open_label' => '07:30-16:30',
    'off_windows' => [
        ['start' => '00:00', 'end' => '07:30'],
        ['start' => '16:30', 'end' => '24:00'],
    ],

    /*
     * Jam istirahat harian: MAN HOUR berhenti (mekanik tidak bekerja),
     * tetapi WORK HOUR tetap berjalan (bengkel tetap beroperasi).
     */
    'breaks' => [
        ['start' => '09:45', 'end' => '10:00'],
        ['start' => '11:30', 'end' => '12:30'],
    ],
];
