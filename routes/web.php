<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Artisan;

Route::get('/foo', function () {
    Artisan::call('storage:link');
});
Route::get('/login', [TicketController::class, 'login'])->name('login');
Route::post('/login/do', [TicketController::class, 'loginDo'])->name('login.do');
Route::middleware(['auth'])->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/scan', [TicketController::class, 'scan'])->name('scan');
    Route::post('/scan/do', [TicketController::class, 'scanDo'])->name('scan.do');
    Route::get('/kupon', [TicketController::class, 'kupon'])->name('kupon');
    Route::post('/generate/kupon/do', [TicketController::class, 'generateKupon'])->name('generate.kupon');
    Route::get('/generate', [TicketController::class, 'generate'])->name('generate');
    Route::get('/generate_g', [TicketController::class, 'generategelang']);
    Route::post('/generate/do', [TicketController::class, 'generateTickets'])->name('generate.do');
    Route::get('/logout/do', [TicketController::class, 'logoutDo'])->name('logout.do');
});
