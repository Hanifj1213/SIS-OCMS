<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\ChecksheetTemplate;
use App\Models\GsheetTemplate;

class DevPanelController extends Controller
{
    /**
     * Halaman utama panel Developer: ringkasan + tautan ke pengelola
     * template GSheet dan template checksheet Receiving/Delivery.
     */
    public function index()
    {
        return view('dev.index', [
            'gsheetCount' => GsheetTemplate::count(),
            'checksheetCount' => ChecksheetTemplate::count(),
        ]);
    }
}
