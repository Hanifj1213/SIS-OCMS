<?php

use App\Http\Controllers\ChecksheetController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\PartRequestController;
use App\Http\Controllers\ProfileController;
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
    $onProgress  = \App\Models\Component::where('status', 'On Progress')->count();
    $readyForUse = \App\Models\Component::where('status', 'Ready for Use')->count();

    // Hitung rata-rata lead time (jam) dari komponen RFU
    $avgLeadTime = 0;
    $rfuComponents = \App\Models\Component::where('status', 'Ready for Use')->get();
    if ($rfuComponents->count() > 0) {
        $totalHours = 0;
        foreach ($rfuComponents as $comp) {
            $firstLog = $comp->overhaulLogs()->orderBy('start_time', 'asc')->first();
            $lastLog  = $comp->overhaulLogs()->orderBy('end_time', 'desc')->first();
            if ($firstLog && $lastLog && $firstLog->start_time && $lastLog->end_time) {
                $totalHours += \Carbon\Carbon::parse($firstLog->start_time)
                    ->diffInHours(\Carbon\Carbon::parse($lastLog->end_time));
            }
        }
        $avgLeadTime = $rfuComponents->count() > 0 ? round($totalHours / $rfuComponents->count(), 1) : 0;
    }

    // Data per-stage untuk chart
    $stageDistribution = [];
    for ($i = 1; $i <= 7; $i++) {
        $stageDistribution[$i] = \App\Models\Component::where('current_stage', $i)
            ->where('status', 'On Progress')
            ->count();
    }

    $pendingParts = \App\Models\PartRequest::where('status', 'Pending')->count();

    return view('dashboard', compact('onProgress', 'readyForUse', 'avgLeadTime', 'stageDistribution', 'pendingParts'));
})->middleware(['auth'])->name('dashboard');

// Grup route yang memerlukan autentikasi
Route::middleware(['auth'])->group(function () {

    // QR Scanner
    Route::get('/scan', function () {
        return view('overhauls.scan');
    })->name('scan');

    // Checksheet routes (Typeform-style)
    Route::get('components/{component}/checksheet/{stage}', [ChecksheetController::class, 'show'])
        ->name('checksheet.show');
    Route::post('components/{component}/checksheet/{stage}/answer', [ChecksheetController::class, 'saveAnswer'])
        ->name('checksheet.saveAnswer');
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

    // Resource route komponen
    Route::resource('components', ComponentController::class);

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
