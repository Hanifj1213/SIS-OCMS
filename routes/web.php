<?php

use App\Http\Controllers\ChecksheetController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\FabricationRequestController;
use App\Http\Controllers\LocalChecksheetController;
use App\Http\Controllers\PartRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

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
    Route::post('components/{component}/fr', [FabricationRequestController::class, 'store'])
        ->name('components.fr.store');
    Route::get('components/{component}/fr/{fr}/pdf', [FabricationRequestController::class, 'pdf'])
        ->name('components.fr.pdf');

    // Resource route komponen (hanya action yang tersedia di controller)
    Route::resource('components', ComponentController::class)
        ->only(['index', 'create', 'store', 'show']);

    // Part Requests (Modul Gudang)
    Route::get('/part-requests', [PartRequestController::class, 'index'])->name('part-requests.index');
    Route::patch('/part-requests/{partRequest}', [PartRequestController::class, 'updateStatus'])->name('part-requests.update');

    // User Management (SuperAdmin only)
    Route::middleware(['role:SuperAdmin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
