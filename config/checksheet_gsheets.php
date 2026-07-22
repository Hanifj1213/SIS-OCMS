<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Apps Script Web App untuk duplikasi template
    |--------------------------------------------------------------------------
    | Deploy script di tools/gsheet_copy_webapp.gs sebagai Web App
    | (Execute as: Me, Access: Anyone), lalu isi URL-nya di .env:
    | GSHEET_COPY_WEBAPP_URL=https://script.google.com/macros/s/..../exec
    */
    'webapp_url' => env('GSHEET_COPY_WEBAPP_URL'),

    // Harus sama dengan variabel SECRET di Apps Script (kosong = tanpa secret)
    'secret' => env('GSHEET_COPY_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Template spreadsheet DISASSEMBLY per EGI (major_category: Engine)
    |--------------------------------------------------------------------------
    | Key = EGI (huruf besar), value = ID spreadsheet Google template.
    | Engine model: SA12V140E-1=WA800-3, SAA6D170E-5=D375-6 & PC1250-8,
    | SAA6D140E-5=D155, SA6D140E-2=GD825A, SAA12V140E-3=PC2000-8.
    */
    'disassembly_templates' => [
        'WA800-3'   => '1zNdwCw65dVaO0zrdX7ttFmGetjWLXKZcApt55yl5iuY', // SA12V140E-1
        'GD825A-2'  => '1FE5c4TiOVPJYvScR_YykmEVvSeHwvB2EcpR0QvMjHrg', // SA6D140E-2
        'GD825A'    => '1FE5c4TiOVPJYvScR_YykmEVvSeHwvB2EcpR0QvMjHrg',
        'D155-6'    => '1YNCACISXD1rspgnI2-zLi1EQi0a4SU4qG9vYXSe7BLI', // SAA6D140E-5
        'D155'      => '1YNCACISXD1rspgnI2-zLi1EQi0a4SU4qG9vYXSe7BLI',
        'D375-6'    => '1LVny6gUcwFpcxpzTJc4GR1kWqAwumE8a-AzBqNWN-9A', // SAA6D170E-5
        'PC1250-8'  => '1LVny6gUcwFpcxpzTJc4GR1kWqAwumE8a-AzBqNWN-9A', // SAA6D170E-5
        'PC2000-8'  => '1kIjBP4R4MWPkpFzXIU7Smcwnyy2DoR2Pzj2oggmn3tY', // SAA12V140E-3 (sheet lama yang sudah dipakai)
    ],

    /*
    |--------------------------------------------------------------------------
    | Template spreadsheet MEASUREMENT per EGI (multi-tab: crankshaft dst.)
    |--------------------------------------------------------------------------
    | Isi ID-nya setelah file di _SIAP_UPLOAD_GSHEET diupload ke Google Sheets.
    | Kosong = fitur measurement belum aktif untuk EGI tsb.
    */
    'measurement_templates' => [
        'WA800-3'   => '16YMihLgPAjpcBZDlmd6DtxocNXJPsPrIpjspZVhduCI', // SA12V140E-1
        'GD825A-2'  => '1Xnmcb6EmX5mCGRvuSfp15TxPNZJnxMfnmnczZkFWDlU', // SA6D140E-2
        'GD825A'    => '1Xnmcb6EmX5mCGRvuSfp15TxPNZJnxMfnmnczZkFWDlU',
        'D155-6'    => '1zRB8oc1KxJM7tbKr0ri6Rl1dAugwdazHh8ky6A27OZM', // SAA6D140E-5
        'D155'      => '1zRB8oc1KxJM7tbKr0ri6Rl1dAugwdazHh8ky6A27OZM',
        'D375-6'    => '1wIxlv5YAhJo90cnBGwSBHqfAMqDa77zgIwQqOeQ2a44', // SAA6D170E-5
        'PC1250-8'  => '1wIxlv5YAhJo90cnBGwSBHqfAMqDa77zgIwQqOeQ2a44',
        'PC2000-8'  => '1fB7lNhIQDDUi-Kb3zqs3QJxzTDPsEqLyQGkP4ilGYqI', // SAA12V140E-3
    ],

    /*
    |--------------------------------------------------------------------------
    | Template SUB ASSY DISASSEMBLY per EGI (multi-tab part)
    |--------------------------------------------------------------------------
    | File: SUBASSY DISASSEMBLY ENGINE ....xlsx — isi ID setelah upload.
    */
    'subassy_disassembly_templates' => [
        'WA800-3'   => '1a5KaeWzZtMVQXQVadDBKBOLc-KDhic-1mVXnJb93-34', // SA12V140E-1
        'GD825A-2'  => '1RbE5UMcVKK3gKwisEIkFVzpCq6mmLwjCPQVSdBE8mLo', // SA6D140E-2
        'GD825A'    => '1RbE5UMcVKK3gKwisEIkFVzpCq6mmLwjCPQVSdBE8mLo',
        'D155-6'    => '1pTpQSLileQzBYoQ1BhO1hAH199-uP8EZ_f71j7M5rBQ', // SAA6D140E-5
        'D155'      => '1pTpQSLileQzBYoQ1BhO1hAH199-uP8EZ_f71j7M5rBQ',
        'D375-6'    => '1uHSrcdykBcn8TNOVMjcmPDyS7scA5bKfqVfmAaWZ_G0', // SAA6D170E-5
        'PC1250-8'  => '1uHSrcdykBcn8TNOVMjcmPDyS7scA5bKfqVfmAaWZ_G0',
        'PC2000-8'  => '1t4ylw-hZViirCqjbqHtTVwggoC5x2s6BAUib-pokVEY', // SAA12V140E-3
    ],

    /*
    |--------------------------------------------------------------------------
    | Template SUB ASSY MEASUREMENT per EGI (multi-tab part)
    |--------------------------------------------------------------------------
    | File: SUBASSY MEASUREMENT ENGINE ....xlsx — isi ID setelah upload.
    */
    'subassy_measurement_templates' => [
        'WA800-3'   => '16lGJ-wrqewzNlwtNVYcBU2B7tRi3UqcMzOXixP0I8do', // SA12V140E-1
        'GD825A-2'  => '1JzQfh5yFWJekOiwZo7osMrfGUy_immS6CoMxPcMhLlc', // SA6D140E-2
        'GD825A'    => '1JzQfh5yFWJekOiwZo7osMrfGUy_immS6CoMxPcMhLlc',
        'D155-6'    => '17K64i-8cBtcFdkTMLK3VDsZRLxVipSUJFZd9_90rCSw', // SAA6D140E-5
        'D155'      => '17K64i-8cBtcFdkTMLK3VDsZRLxVipSUJFZd9_90rCSw',
        'D375-6'    => '13PkmzN45Hl--GFSkwYh4rxndAeJj8Zu9XAa0C2OYx5w', // SAA6D170E-5
        'PC1250-8'  => '13PkmzN45Hl--GFSkwYh4rxndAeJj8Zu9XAa0C2OYx5w',
        'PC2000-8'  => '1-3tBQhnbKSy0qy-GLhTcXRs9XBA1X0gvKrB_sNz-pM8', // SAA12V140E-3
    ],

];
