<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\ResidentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Resident\ResidentDashboardController;
use App\Http\Controllers\Resident\ResidentEmergencyController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffIncidentController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/login', function (Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|string|max:255',
        'password' => 'required|string',
    ], [
        'email.required' => 'Enter your username or email.',
        'email.string' => 'Enter a valid username or email.',
        'email.max' => 'Your username or email must not exceed 255 characters.',
        'password.required' => 'Enter your password.',
        'password.string' => 'Enter a valid password.',
    ]);

    $identifier = $credentials['email'];
    $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    if (Auth::attempt([$column => $column === 'username' ? strtolower($identifier) : $identifier, 'password' => $credentials['password'], 'is_active' => true])) {
        $request->session()->regenerate();

        // Route based on user role
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->put('auth_version', (int) $user->auth_version);
        if ($user->role === 'resident') {
            return redirect()->intended(route('resident.dashboard'));
        }

        if ($user->role === 'staff') {
            return redirect()->intended('/staff/staffdashboard');
        }

        return redirect()->intended('/admin/dashboard');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->middleware('throttle:10,1')->name('login.post');

Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
Route::get('/account', [\App\Http\Controllers\AccountController::class, 'show'])->middleware('auth')->name('account.show');
Route::patch('/account', [\App\Http\Controllers\AccountController::class, 'update'])->middleware(['auth', 'throttle:10,1,account-profile:'])->name('account.update');
Route::put('/account/password', [\App\Http\Controllers\AccountController::class, 'password'])->middleware(['auth', 'throttle:5,1,account-password:'])->name('account.password');

Route::get('/admin/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'role:admin']);

Route::post('/logout', function (Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Resident-facing portal routes
Route::middleware('auth')->prefix('resident')->name('resident.')->group(function () {
    // Dashboard overview
    Route::get('/dashboard', [ResidentDashboardController::class, 'index'])->name('dashboard');

    // Personal profile page
    Route::get('/profile', [ResidentDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [ResidentDashboardController::class, 'updateProfile'])->name('profile.update');

    // Emergency / Incident Report
    Route::get('/emergency/create', [ResidentEmergencyController::class, 'create'])->name('emergency.create');
    Route::post('/emergency', [ResidentEmergencyController::class, 'store'])->name('emergency.store');

    // Community Alerts
    Route::get('/alerts', [ResidentDashboardController::class, 'alerts'])->name('alerts');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/summary-reports', [\App\Http\Controllers\Admin\SummaryReportController::class, 'index'])->name('summary-reports.index');
    Route::view('/settings', 'admin.settings')->name('settings.index');

    // Incident Routes
    Route::get('/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/archives', [IncidentController::class, 'index'])->name('archives.index');
    Route::get   ('/incidents',                [IncidentController::class, 'index']        )->name('incidents.index');
    Route::get   ('/incidents/create',         [IncidentController::class, 'create']       )->name('incidents.create');
    Route::post  ('/incidents',                [IncidentController::class, 'store']        )->name('incidents.store');
    Route::get   ('/incidents/{incident}',     [IncidentController::class, 'show']         )->name('incidents.show');
    Route::patch ('/incidents/{incident}/status',  [IncidentController::class, 'updateStatus'])->name('incidents.status');
    Route::patch ('/incidents/{incident}/archive', [IncidentController::class, 'archive']  )->name('incidents.archive');

    // Emergency Alert Routes
    Route::get   ('/emergency',               [AlertController::class, 'index']  )->name('emergency.index');
    Route::get('/emergency/create', [\App\Http\Controllers\Admin\EmergencyRecordController::class,'create'])->name('emergency.create');
    Route::post('/emergency', [\App\Http\Controllers\Admin\EmergencyRecordController::class,'store'])->name('emergency.store');
    Route::get('/emergency/{emergency}', [\App\Http\Controllers\Admin\EmergencyRecordController::class,'show'])->name('emergency.show');
    Route::get('/emergency/{emergency}/edit', [\App\Http\Controllers\Admin\EmergencyRecordController::class,'edit'])->name('emergency.edit');
    Route::put('/emergency/{emergency}/record', [\App\Http\Controllers\Admin\EmergencyRecordController::class,'update'])->name('emergency.record.update');
    Route::put   ('/emergency/{emergency}',   [AlertController::class, 'update'] )->name('emergency.update');
    Route::patch ('/emergency/{emergency}/toggle', [AlertController::class, 'toggle'])->name('emergency.toggle');
    Route::delete('/emergency/{emergency}',   [AlertController::class, 'destroy'])->name('emergency.destroy');

    Route::get('/incidents/{incident}/edit', [IncidentController::class, 'edit'])->name('incidents.edit');
    Route::put('/incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');

    // Resident Routes
    Route::get  ('/residents',               [ResidentController::class, 'index'] )->name('residents.index');
    Route::get  ('/residents/create',        [ResidentController::class, 'create'])->name('residents.create');
    Route::post ('/residents',               [ResidentController::class, 'store'] )->name('residents.store');
    Route::get  ('/residents/{resident}',    [ResidentController::class, 'show'] )->name('residents.show');
    Route::put  ('/residents/{resident}',    [ResidentController::class, 'update'])->name('residents.update');

    Route::get('/residents/{resident}/edit', [ResidentController::class, 'edit'])->name('residents.edit');

    // JSON stats endpoint (used by dashboard.js + incidents.js auto-refresh)
    Route::get('/dashboard/stats', [IncidentController::class, 'stats'])->name('dashboard.stats');

    // User Management Routes
    Route::get    ('/users',          [UserController::class, 'index']  )->name('users.index');
    Route::post   ('/users',          [UserController::class, 'store']  )->name('users.store');
    Route::put    ('/users/{user}',   [UserController::class, 'update'] )->name('users.update');
    Route::delete ('/users/{user}',   [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/status', [UserController::class, 'status'])->name('users.status');
    Route::post('/users/{user}/password-reset', [UserController::class, 'resetPassword'])->middleware('throttle:5,1')->name('users.password-reset');

});

// Staff dashboard routes
Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/staffdashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
    Route::get('/summary-reports', [\App\Http\Controllers\Admin\SummaryReportController::class, 'index'])->name('summary-reports.index');

    // Shared record implementation, protected by Staff-only middleware.
    Route::get('/disaster', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/disaster/create', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/disaster', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/disaster/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::get('/disaster/{incident}/edit', [IncidentController::class, 'edit'])->name('incidents.edit');
    Route::put('/disaster/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
    Route::patch('/disaster/{incident}/status', [IncidentController::class, 'updateStatus'])->name('incidents.status');
    Route::get('/dashboard/stats', [IncidentController::class, 'stats'])->name('dashboard.stats');

    Route::get('/emergency', [AlertController::class, 'index'])->name('emergency.index');
    Route::get('/emergency/create', [\App\Http\Controllers\Admin\EmergencyRecordController::class, 'create'])->name('emergency.create');
    Route::post('/emergency', [\App\Http\Controllers\Admin\EmergencyRecordController::class, 'store'])->name('emergency.store');
    Route::get('/emergency/{emergency}', [\App\Http\Controllers\Admin\EmergencyRecordController::class, 'show'])->name('emergency.show');
    Route::get('/emergency/{emergency}/edit', [\App\Http\Controllers\Admin\EmergencyRecordController::class, 'edit'])->name('emergency.edit');
    Route::put('/emergency/{emergency}/record', [\App\Http\Controllers\Admin\EmergencyRecordController::class, 'update'])->name('emergency.record.update');

    Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
    Route::get('/residents/create', [ResidentController::class, 'create'])->name('residents.create');
    Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store');
    Route::get('/residents/{resident}', [ResidentController::class, 'show'])->name('residents.show');
    Route::get('/residents/{resident}/edit', [ResidentController::class, 'edit'])->name('residents.edit');
    Route::put('/residents/{resident}', [ResidentController::class, 'update'])->name('residents.update');

    // Preserve existing Staff incident URLs and status workflow.

    Route::get('/incidentmanagement', fn () => redirect()->route('staff.incidents.index'))->name('legacy.incidents.index');

    Route::get('/incidentmanagement/{incident}', [App\Http\Controllers\Staff\StaffIncidentController::class, 'show'])
        ->name('legacy.incidents.show');

    Route::put('/incidentmanagement/{incident}/status', [App\Http\Controllers\Staff\StaffIncidentController::class, 'updateStatus'])
        ->name('legacy.incidents.status');

});
