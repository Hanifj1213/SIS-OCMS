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

];
