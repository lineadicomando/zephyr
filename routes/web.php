<?php

use App\Http\Controllers\Web\ScopeSwitchController;
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

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware('auth')->group(function (): void {
    Route::post('/scopes/{scope}/switch', ScopeSwitchController::class)
        ->name('scopes.switch');
});
