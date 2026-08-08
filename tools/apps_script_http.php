<?php
/**
 * POST ke Google Apps Script Web App dengan benar.
 *
 * Alur GAS:
 *  1) POST body ke .../exec  → 302 Location (macros/echo?...)
 *  2) GET Location tersebut  → hasil doPost (JSON)
 *
 * Kalau follow-redirect mengubah POST→GET terlalu dini, atau
 * POST ulang ke echo URL, hasilnya HTTP 405.
 */
function postToAppsScript(string $url, array $payload, int $timeout = 180): \Illuminate\Http\Client\Response
{
    $json = json_encode($payload);
    $connectTimeout = min(60, max(15, (int) ceil($timeout / 5)));

    // force_ip_resolve v4: resolusi IPv6 ke *.googleusercontent.com kadang
    // menggantung ±21 detik di Windows lalu gagal (cURL error 28).
    $first = Illuminate\Support\Facades\Http::timeout($timeout)
        ->connectTimeout($connectTimeout)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->withBody($json, 'application/json')
        ->withOptions(['allow_redirects' => false, 'force_ip_resolve' => 'v4'])
        ->post($url);

    if (!in_array($first->status(), [301, 302, 303, 307, 308], true)) {
        return $first;
    }

    $location = $first->header('Location');
    if (!$location) {
        return $first;
    }

    if (!str_starts_with($location, 'http')) {
        $parts = parse_url($url);
        $location = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $location;
    }

    // Penting: echo URL diikuti dengan GET — body POST sudah diproses di
    // langkah 1, jadi GET ini AMAN di-retry tanpa membuat upload ganda.
    return Illuminate\Support\Facades\Http::timeout($timeout)
        ->connectTimeout($connectTimeout)
        ->retry(4, 3000, throw: false)
        ->withHeaders(['Accept' => 'application/json'])
        ->withOptions(['allow_redirects' => false, 'force_ip_resolve' => 'v4'])
        ->get($location);
}
