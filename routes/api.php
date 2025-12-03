<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SyncroController;
use App\Http\Controllers\AiMatchingController;
use App\Http\Controllers\PersonalityJudgmentController;
use App\Http\Controllers\admin\AnnouncementController;
use App\Http\Controllers\Image\GeminiImageController;
use App\Http\Controllers\Image\DeepImageController;
use App\Http\Controllers\PersonalityAssessmentController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\admin\GeojsonController;
use App\Http\Controllers\CampaignDiscountApiController;

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

Route::post('/handle-invite', [UserController::class, 'handleInvite']);

Route::post('/chat', [ChatbotController::class, 'chat']);
Route::post('/chat-venice', [ChatbotController::class, 'chatVenice']);
Route::post('/chatWithGemini', [ChatbotController::class, 'chatWithGemini']);

Route::get('/profile/{userId}', [ProfileController::class, 'show']);
Route::get('/profile/{userId}/blood-type-bday', [ProfileController::class, 'getBloodTypeNBDay']);
Route::post('/profile/{userId}', [ProfileController::class, 'update']);

Route::get('/syncro/{userId}', [SyncroController::class, 'show']);

Route::post('/report/{id}', [UserController::class, 'postReport']);

Route::get('/get-point/{userId}', [SyncroController::class, 'getPoint']);
Route::post('/add-point/{userId}', [SyncroController::class, 'addPoint']);
Route::post('/remove-point/{userId}', [SyncroController::class, 'removePoint']);

// 検索機能
Route::post('/matching/get-users', [AiMatchingController::class, 'getUsers']);
Route::post('/matching/delete-friend', [AiMatchingController::class, 'deleteFriend']);

Route::post('/matching/get-date-users', [MatchController::class, 'rank']);

Route::post('/matching/select-friend', [AiMatchingController::class, 'selectFriend']);
Route::post('/matching/chat-with-friend', [ChatbotController::class, 'chatWithFriend']);

Route::post('/matching/invite-friend', [AiMatchingController::class, 'inviteFriend']);
Route::post('/matching/match-friend', [AiMatchingController::class, 'matchFriend']);
Route::post('/matching/reject-friend', [AiMatchingController::class, 'rejectFriend']);

Route::post('/get-boost-count', [UserController::class, 'getBoostCount']);

// GEMINI画像生成
Route::post('/generate-avatar-gemini', [GeminiImageController::class, 'generateAvatar']);

// announcements
Route::get('/get-announcements', [AnnouncementController::class, 'getAnnouncements']);

// mbti questions
Route::get('/get-mbti-questions', [PersonalityJudgmentController::class, 'fetchMBTIQuestions']);

Route::apiResource('personality-assessments', PersonalityAssessmentController::class);

Route::post('/purchase-boost', [UserController::class, 'purchaseBoostMode']);
Route::post('/use-trial-boost', [UserController::class, 'useTrialBoostMode']);

Route::post('/subscribe-live-chat', [UserController::class, 'subscribeLiveChat']);
Route::post('/get-subscription-date', [UserController::class, 'getSubscriptionDate']);

// disaster facilities
Route::post('/get-nearby-disaster-facilities', [GeojsonController::class, 'getNearbyDisasterFacilities']);

// discount
Route::get('/get-discount', [CampaignDiscountApiController::class, 'today']);