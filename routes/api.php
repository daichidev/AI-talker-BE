<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SyncroController;
use App\Http\Controllers\AiMatchingController;

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
Route::post('/reset-avatar', [UserController::class, 'resetAvatar']);

Route::post('/login-with-face-id', [UserController::class, 'loginWithFaceID']);

Route::post('/login', [UserController::class, 'login']);

Route::post('/delete-account', [UserController::class, 'deleteAccount']);

Route::post('/anketo', [UserController::class, 'storeAnketo']);
Route::post('/anketo/question', [UserController::class, 'getQuestion']);

Route::post('/personality-test', [UserController::class, 'personalityTest']);
Route::get('/personality-test/{id}', [UserController::class, 'getPersonalityTest']);

Route::post('/chat', [ChatbotController::class, 'chat']);
Route::post('/chatWithGemini', [ChatbotController::class, 'chatWithGemini']);

Route::get('/profile/{userId}', [ProfileController::class, 'show']);
Route::post('/profile/{userId}', [ProfileController::class, 'update']);

Route::get('/syncro/{userId}', [SyncroController::class, 'show']);

Route::post('/report/{id}', [UserController::class, 'postReport']);

// 検索機能
Route::post('/matching/select-users', [AiMatchingController::class, 'searchUsers']);
Route::post('/matching/select-user', [AiMatchingController::class, 'selectUser']);