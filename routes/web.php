<?php

use App\Helpers\EncryptionHelper;
use App\Http\Controllers\ManifestoTracker\ManifestoTrackerManagementController;
use App\Http\Controllers\PostController\FetchPostController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
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
Route::get('/template', function () {
    return view('email.template');
});
Route::get('/sharedPost/{id}', [FetchPostController::class, 'sharedPostOpen'])->name('sharedPostOpen');


Route::get('/sharedManifesto/{id}', [ManifestoTrackerManagementController::class, 'sharedManifesto'])->name('sharedManifesto');

Route::get('/register', [UserController::class, 'create'])->name('register');
Route::get('/logs', function () {
    return view('logs');
});

