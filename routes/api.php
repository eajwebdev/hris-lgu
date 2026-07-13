<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DtrController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\TimeEntryDtrController;
use App\Http\Controllers\Api\JobHiringController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\CoasController;
use App\Http\Controllers\Api\GadController;
use App\Http\Controllers\Api\CptController;


Route::post('/dtrs', [DtrController::class, 'syncDtr'])->name('api.syncDtr');
Route::post('/dtrs-batch', [DtrController::class, 'syncDtrBatch'])->name('api.syncDtrBatch');
Route::get('/event-list/{passcode}', [EventController::class, 'eventList'])->name('api.eventList');
Route::get('/event-login/{passcode}/{eventid}/{empid}', [EventController::class, 'eventLogin'])->name('api.eventLogin');
Route::get('/event-logs/{passcode}/{eventid}', [EventController::class, 'eventLogs'])->name('api.eventLogs');

Route::get('/job-list', [JobHiringController::class, 'jobList'])->name('api.jobList');
Route::post('/application/store', [ApplicationController::class, 'applicationStore'])->name('application.store');
Route::get('/application/check/{jid}/{email}', [ApplicationController::class, 'applicationCheck'])->name('application.check');
Route::get('/application/status/{appnumber}', [ApplicationController::class, 'applicationStatus'])->name('application.status');
Route::get('/gad-gender-count', [GadController::class, 'genderCount'])->name('gender-count');

Route::get('/cpt/sync', [CptController::class, 'sync']);

// DTR
Route::prefix('app-dtr')->group(function () {
    // Static routes MUST come before wildcard routes to avoid conflicts
    Route::post('/search', [TimeEntryDtrController::class, 'dtrSearch'])->name('app-dtr-search');
    // Route::get('/pdf/{empid}/{period}/{date}/{overtime}/{filename}', [TimeEntryDtrController::class, 'dtrPdf'])->name('app-dtr-pdf');
    Route::get('/pdf/{empid}/{period}/{date}/{overtime}/{filename}', 
        [TimeEntryDtrController::class, 'dtrPdf']
    )->where('filename', '.*')
    ->name('app-dtr-pdf');

    // Route::get('/logs/{empid}', [TimeEntryDtrController::class, 'dtrLogs'])->name('app-dtr-logs');
    
    // Wildcard route must be LAST to prevent catching static paths like /search
    Route::post('/{empid}', [TimeEntryDtrController::class, 'dtrRead'])->name('app-dtr-read');
});
// Route::get('/emp-sig',[CoasController::class,'empSig'])->name('empSig');

Route::prefix('app')->group(function() {
    Route::get('/dtrlogs', [DtrController::class, 'appdtrLogs'])->name('appdtrLogs');
    Route::get('/authcheck', [DtrController::class, 'appdtrauthCheck'])->name('appdtrauthcheck');
    Route::get('/authlogin', [DtrController::class, 'appdtrauthLogin'])->name('appdtrauthLogin');
    Route::get('/check-coordinates', [DtrController::class, 'checkCoordinates'])->name('checkCoordinates');
    
    // CPSU TIME ENTRY
    // ── Health / readiness ─────────────────────────────────────────────
    Route::post('/health', fn(Request $r) => response()->noContent());

    // ── Public/employee flows (primary UX) ─────────────────────────────
    Route::post('/check-restriction-level', [TimeEntryController::class, 'checkRestrictionLevel']);
    Route::post('/fetch-license', [TimeEntryController::class, 'fetchLicense']);
    Route::post('/fetch-logzones-with-campuses', [TimeEntryController::class, 'fetchLogzonesWithCampuses']);
    Route::post('/validate-qr', [TimeEntryController::class, 'validateQr']);
    Route::post('/face-claim', [TimeEntryController::class, 'faceClaim']);
    Route::post('/log-attendance', [TimeEntryController::class, 'logAttendance']);
    Route::post('/fetch-latest-logs', [TimeEntryController::class, 'fetchLatestLogs']);
    Route::post('/download-dtr', [TimeEntryController::class, 'downloadDtr']);

    // ── Admin/kiosk flows ──────────────────────────────────────────────
    Route::post('/admin-face-claim', [TimeEntryController::class, 'adminFaceClaim']);
    Route::post('/admin-pass-verify', [TimeEntryController::class, 'adminPassVerify']);

    // ── Directory/registration utilities ───────────────────────────────
    Route::post('/fetch-employees', [TimeEntryController::class, 'fetchEmployees']);
    Route::post('/face-register', [TimeEntryController::class, 'faceRegister']);

    // ── Deprecated (leave for compatibility; keep at end) ──────────────
    Route::post('/fetch-logzones', [TimeEntryController::class, 'fetchLogzones']);          // deprecated
    Route::post('/face-verify', [TimeEntryController::class, 'faceVerify']);                // deprecated
    Route::post('/admin-face-verify', [TimeEntryController::class, 'adminFaceVerify']);     // deprecated
});

// Route::prefix('clinic')->group(function() {
//     Route::get('/employees', [ClinicController::class, 'emplList']);    
// });