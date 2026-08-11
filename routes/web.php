<?php

use App\Http\Controllers\AssemblyDocumentController;
use App\Http\Controllers\ChecksheetController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\Dev\ChecksheetTemplateController as DevChecksheetTemplateController;
use App\Http\Controllers\Dev\DevPanelController;
use App\Http\Controllers\Dev\GsheetTemplateController as DevGsheetTemplateController;
use App\Http\Controllers\FabricationRequestController;
use App\Http\Controllers\LocalChecksheetController;
use App\Http\Controllers\MolController;
use App\Http\Controllers\PaintingPhotoController;
use App\Http\Controllers\PartRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StageTimeController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Health check — deployment smoke test (no auth, no sensitive data)
Route::get('/up', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Throwable) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database unreachable',
        ], 503);
    }
})->name('health');

// Landing Page (Pre-Login)
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Dashboard — TANPA middleware 'verified' karena kita tidak pakai email verification
Route::get('/dashboard', function () {
    return view('dashboard', StatusController::dashboardMetrics());
})->middleware(['auth'])->name('dashboard');

// Grup route yang memerlukan autentikasi
Route::middleware(['auth'])->group(function () {

    // QR Scanner
    Route::get('/scan', function () {
        return view('overhauls.scan');
    })->name('scan');

    // Status polling (realtime update tanpa refresh)
    Route::get('/status/dashboard', [StatusController::class, 'dashboard'])->name('status.dashboard');
    Route::get('/status/components', [StatusController::class, 'components'])->name('status.components');
    Route::get('/status/components/{component}', [StatusController::class, 'component'])->name('status.component');
    Route::get('/status/part-requests', [StatusController::class, 'partRequests'])->name('status.partRequests');

    // Checksheet routes (Typeform-style)
    Route::get('components/{component}/checksheet/{stage}', [ChecksheetController::class, 'show'])
        ->name('checksheet.show');
    Route::post('components/{component}/checksheet/{stage}/answer', [ChecksheetController::class, 'saveAnswer'])
        ->name('checksheet.saveAnswer');
    Route::post('components/{component}/spreadsheet-checksheet/{stage}', [ChecksheetController::class, 'saveSpreadsheet'])
        ->name('checksheet.saveSpreadsheet');
    Route::post('components/{component}/checksheet/{stage}/add-item', [ChecksheetController::class, 'addItem'])
        ->name('checksheet.addItem');
    Route::delete('components/{component}/checksheet/{stage}/remove-item', [ChecksheetController::class, 'removeItem'])
        ->name('checksheet.removeItem');

    // Custom component routes (HARUS didefinisikan SEBELUM resource route)
    Route::get('components/{component}/print-pdf', [ComponentController::class, 'printPdf'])
        ->name('components.printPdf');
    Route::post('components/{component}/update-stage', [ComponentController::class, 'updateStage'])
        ->name('components.updateStage');
    Route::post('components/{component}/approve-stage', [ComponentController::class, 'approveStage'])
        ->name('components.approveStage');
    Route::post('components/{component}/reject-stage', [ComponentController::class, 'rejectStage'])
        ->name('components.rejectStage');
    // Pelacakan waktu 3 dimensi (Calendar/Work/Man Hour) + crew mekanik
    Route::get('components/{component}/time-metrics', [StageTimeController::class, 'metrics'])
        ->name('components.timeMetrics');
    Route::post('components/{component}/crew', [StageTimeController::class, 'addMechanic'])
        ->name('components.crew.add');
    Route::delete('components/{component}/crew/{log}', [StageTimeController::class, 'removeMechanic'])
        ->name('components.crew.remove');
    // Stage 5 (Test Performance & Painting): dokumentasi foto hasil pengecatan
    Route::post('components/{component}/painting/photos', [PaintingPhotoController::class, 'upload'])
        ->name('components.painting.upload');
    Route::delete('components/{component}/painting/photos', [PaintingPhotoController::class, 'destroy'])
        ->name('components.painting.delete');
    // Stage 4 (Assembly): dokumen (PDF) / foto dokumentasi perakitan
    Route::post('components/{component}/assembly/documents', [AssemblyDocumentController::class, 'upload'])
        ->name('components.assembly.upload');
    Route::delete('components/{component}/assembly/documents', [AssemblyDocumentController::class, 'destroy'])
        ->name('components.assembly.delete');

    // Checksheet spreadsheet lokal (tampilan 1:1 Excel, data di database)
    Route::get('checksheet-layouts', [LocalChecksheetController::class, 'index'])
        ->name('checksheet.layouts');
    Route::get('checksheet-layouts/{layout}', [LocalChecksheetController::class, 'preview'])
        ->name('checksheet.layouts.preview');
    Route::get('components/{component}/local-checksheet/{kind}', [LocalChecksheetController::class, 'show'])
        ->name('components.local-checksheet');
    Route::post('components/{component}/local-checksheet/{kind}/cell', [LocalChecksheetController::class, 'saveCell'])
        ->name('components.local-checksheet.cell');

    // Fabrication Request (FR) — Stage 2+
    Route::get('components/{component}/fr', [FabricationRequestController::class, 'index'])
        ->name('components.fr.index');
    Route::post('components/{component}/fr/scan', [FabricationRequestController::class, 'scan'])
        ->name('components.fr.scan');
    // 'create' harus sebelum '{fr}' agar tidak tertangkap route model binding
    Route::get('components/{component}/fr/create', [FabricationRequestController::class, 'create'])
        ->name('components.fr.create');
    Route::post('components/{component}/fr/single', [FabricationRequestController::class, 'storeSingle'])
        ->name('components.fr.storeSingle');
    Route::post('components/{component}/fr', [FabricationRequestController::class, 'store'])
        ->name('components.fr.store');
    Route::get('components/{component}/fr/{fr}/edit', [FabricationRequestController::class, 'edit'])
        ->name('components.fr.edit');
    Route::put('components/{component}/fr/{fr}', [FabricationRequestController::class, 'update'])
        ->name('components.fr.update');
    Route::patch('components/{component}/fr/{fr}/status', [FabricationRequestController::class, 'updateStatus'])
        ->name('components.fr.update-status');
    Route::get('components/{component}/fr/{fr}/pdf', [FabricationRequestController::class, 'pdf'])
        ->name('components.fr.pdf');
    // MOL (Mechanic Order List) — form kosong mengikuti template MOL.xlsx tab "ADD 1"
    Route::get('components/{component}/mol', [MolController::class, 'create'])
        ->name('components.mol.create');
    Route::post('components/{component}/mol', [MolController::class, 'store'])
        ->name('components.mol.store');
    // Export PDF MOL otomatis dihapus — dokumen MOL diisi manual oleh mekanik
    // lalu diunggah lewat components.mol.upload-document.
    Route::post('components/{component}/mol/document', [MolController::class, 'uploadDocument'])
        ->name('components.mol.upload-document');
    Route::delete('components/{component}/mol/document', [MolController::class, 'deleteDocument'])
        ->name('components.mol.delete-document');

    // Edit komponen — Developer & SuperAdmin (dicek juga di controller)
    Route::get('components/{component}/edit', [ComponentController::class, 'edit'])
        ->middleware('role:SuperAdmin|Developer')
        ->name('components.edit');
    Route::put('components/{component}', [ComponentController::class, 'update'])
        ->middleware('role:SuperAdmin|Developer')
        ->name('components.update');

    // Resource route komponen (hanya action yang tersedia di controller)
    Route::resource('components', ComponentController::class)
        ->only(['index', 'create', 'store', 'show']);

    // Hapus komponen — Developer & SuperAdmin (dicek juga di controller)
    Route::delete('components/{component}', [ComponentController::class, 'destroy'])
        ->middleware('role:SuperAdmin|Developer')
        ->name('components.destroy');

    // Part Requests (Modul Gudang)
    Route::get('/part-requests', [PartRequestController::class, 'index'])->name('part-requests.index');
    Route::patch('/part-requests/{partRequest}', [PartRequestController::class, 'updateStatus'])->name('part-requests.update');

    // Panel Developer — kelola template GSheet & checksheet tanpa ubah kode
    Route::middleware(['role:SuperAdmin|Developer'])->prefix('dev')->name('dev.')->group(function () {
        Route::get('/', [DevPanelController::class, 'index'])->name('index');

        Route::get('/gsheet-templates', [DevGsheetTemplateController::class, 'index'])->name('gsheet-templates.index');
        Route::post('/gsheet-templates', [DevGsheetTemplateController::class, 'store'])->name('gsheet-templates.store');
        Route::patch('/gsheet-templates/{gsheetTemplate}', [DevGsheetTemplateController::class, 'update'])->name('gsheet-templates.update');
        Route::delete('/gsheet-templates/{gsheetTemplate}', [DevGsheetTemplateController::class, 'destroy'])->name('gsheet-templates.destroy');

        Route::get('/checksheet-templates', [DevChecksheetTemplateController::class, 'index'])->name('checksheet-templates.index');
        Route::post('/checksheet-templates', [DevChecksheetTemplateController::class, 'store'])->name('checksheet-templates.store');
        Route::get('/checksheet-templates/{checksheetTemplate}/edit', [DevChecksheetTemplateController::class, 'edit'])->name('checksheet-templates.edit');
        Route::put('/checksheet-templates/{checksheetTemplate}', [DevChecksheetTemplateController::class, 'update'])->name('checksheet-templates.update');
        Route::delete('/checksheet-templates/{checksheetTemplate}', [DevChecksheetTemplateController::class, 'destroy'])->name('checksheet-templates.destroy');
        Route::post('/checksheet-templates/{checksheetTemplate}/image', [DevChecksheetTemplateController::class, 'uploadImage'])->name('checksheet-templates.image.upload');
        Route::delete('/checksheet-templates/{checksheetTemplate}/image', [DevChecksheetTemplateController::class, 'deleteImage'])->name('checksheet-templates.image.delete');
    });

    // User Management (SuperAdmin only)
    Route::middleware(['role:SuperAdmin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/password', [UserManagementController::class, 'editPassword'])->name('users.password.edit');
        Route::patch('/users/{user}/password', [UserManagementController::class, 'updatePassword'])->name('users.password.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
