<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityVisitController;
use App\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/cities', [CityController::class, 'index']);

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/cities/{city}/visits', [CityVisitController::class, 'index']);
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->whereNumber('lead');
});

/*
| Painel da equipe interna. Todas exigem usuário da equipe (role); parceiros recebem 403.
| admin: tudo · field_agent: atendimentos (só ver) e indicações · factory: só entra.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware(['auth:api', 'role'])->group(function () {
        Route::get('/me', [Admin\AuthController::class, 'me'])->name('me');
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

        Route::middleware('role:admin,field_agent')->group(function () {
            Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

            Route::get('/cities', [Admin\CityController::class, 'index'])->name('cities.index');
            Route::get('/prospectors', [Admin\ProspectorController::class, 'index'])->name('prospectors.index');

            Route::get('/city-visits', [Admin\CityVisitController::class, 'index'])->name('city-visits.index');
            Route::get('/city-visits/{cityVisit}', [Admin\CityVisitController::class, 'show'])->name('city-visits.show');

            Route::get('/leads', [Admin\LeadController::class, 'index'])->name('leads.index');
            Route::get('/leads/{lead}', [Admin\LeadController::class, 'show'])->name('leads.show');
            Route::patch('/leads/{lead}/schedule', [Admin\LeadController::class, 'schedule'])->name('leads.schedule');
            Route::patch('/leads/{lead}/status', [Admin\LeadController::class, 'updateStatus'])->name('leads.status');
            Route::post('/leads/{lead}/contacts', [Admin\LeadController::class, 'storeContact'])->name('leads.contacts.store');
        });

        Route::middleware('role:admin')->group(function () {
            Route::post('/city-visits', [Admin\CityVisitController::class, 'store'])->name('city-visits.store');
            Route::patch('/city-visits/{cityVisit}', [Admin\CityVisitController::class, 'update'])->name('city-visits.update');

            Route::apiResource('users', Admin\TeamUserController::class)->except('show');
        });
    });
});
