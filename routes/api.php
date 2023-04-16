<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


use App\Http\Controllers\IngestController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('countries', function () {
    echo "<pre>";
    $sql = 'SELECT * FROM country';

    $results = DB::select($sql);
    return json_encode($results);
});


Route::post('postWeatherData', [IngestController::class, 'ingestData']);
