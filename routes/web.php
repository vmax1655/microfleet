<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Fleet;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Intelligence;
use App\Livewire\Logistics;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Microfleet routes
|--------------------------------------------------------------------------
| Route names mirror App\Support\Nav so the sidebar, active states, and
| breadcrumbs stay aligned to the Fleet & Transportation subsystem modules.
*/

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::match(['get', 'post'], '/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    if (request()->query('reason') === 'timeout') {
        return redirect()->route('login')->with('status', 'Your session expired due to inactivity. Please sign in again.');
    }

    return redirect()->route('login');
})->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/session/keepalive', function () {
        return response()->json(['status' => 'active', 'timestamp' => now()->timestamp]);
    })->name('session.keepalive');

    Route::get('/security/two-factor', App\Livewire\Auth\TwoFactorSettings::class)->name('security.two-factor');

    Route::post('/depot/switch', function (\Illuminate\Http\Request $request) {
        $depotName = $request->input('depot_name');
        if ($depotName) {
            session(['active_depot' => $depotName]);
            auth()->user()?->update(['branch' => $depotName]);
        }
        return response()->json(['success' => true, 'branch' => $depotName]);
    })->name('depot.switch');
});

Route::middleware(['auth', 'rbac'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Fleet & Vehicle Management  ·  reservation-dispatch  ·  driver-trip
    Route::prefix('fleet')->name('fleet.')->group(function () {
        Route::get('/vehicles', Fleet\Vehicles::class)->name('vehicles');
        Route::get('/drivers', Fleet\Drivers::class)->name('drivers');
        Route::get('/reservations', Fleet\Reservations::class)->name('reservations');
        Route::get('/dispatch', Fleet\Dispatch::class)->name('dispatch');
        Route::get('/trips', Fleet\Trips::class)->name('trips');
    });

    // Logistics  ·  fleet-vehicle · fuel · cost-optimization · route-optimization
    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::get('/fuel', Logistics\Fuel::class)->name('fuel');
        Route::get('/expenses', Logistics\Expenses::class)->name('expenses');
        Route::get('/maintenance', Logistics\Maintenance::class)->name('maintenance');
        Route::get('/routes', Logistics\Routes::class)->name('routes');
        Route::get('/depots', Logistics\Depots::class)->name('depots');
    });

    // Intelligence  ·  cost-optimization · route-optimization
    Route::prefix('intelligence')->name('intelligence.')->group(function () {
        Route::get('/costs', Intelligence\Costs::class)->name('costs');
        Route::get('/ml', Intelligence\Ml::class)->name('ml');
        Route::get('/variance', Intelligence\Variance::class)->name('variance');
        Route::get('/efficiency', Intelligence\Efficiency::class)->name('efficiency');
        Route::get('/reports/export/{report}', ReportExportController::class)->name('reports.export');
        Route::get('/reports', Intelligence\Reports::class)->name('reports');
    });
});
