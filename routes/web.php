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

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin'], function () {
    Route::get('documentation', [\App\Http\Controllers\DocumentationController::class, 'index'])
        ->name('voyager.documentation.index')
        ->middleware('admin.user'); // Ensure strictly admin access
    Voyager::routes();
});
