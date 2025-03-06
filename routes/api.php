<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatbotController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', function() {
    return response()->json("test success");
});

Route::post('/store-face-id', [UserController::class, 'storeFaceID']);

Route::post('/login-with-face-id', [UserController::class, 'loginWithFaceID']);

Route::post('/anketo', [UserController::class, 'storeAnketo']);
Route::post('/anketo/question', [UserController::class, 'getQuestion']);

Route::post('/login', [UserController::class, 'login']);

Route::post('/chat', [ChatbotController::class, 'chat']);