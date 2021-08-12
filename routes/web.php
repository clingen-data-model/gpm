<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ViewController;
use App\Http\Controllers\DocumentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/{any}', [ViewController::class, 'app'])
    ->where('any', '^(?!(api|sanctum|admin|documents|report|downloads)).*$');

Route::get('/documents/{uuid?}', [DocumentController::class, 'show'])->middleware('auth:sanctum');
