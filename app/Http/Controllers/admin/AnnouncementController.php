<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\User;
use Carbon\Carbon;
use App\Services\GoogleAccessTokenService;
use Illuminate\Support\Facades\Log; // For better error logging
use Exception; // For catching token generation errors

class AnnouncementController extends Controller
{
    protected GoogleAccessTokenService $accessTokenService;
    protected string $firebaseProjectId;

    // Constructor to inject the Access Token Service
    public function __construct(GoogleAccessTokenService $accessTokenService)
    {
        $this->accessTokenService = $accessTokenService;
        $this->firebaseProjectId = 'myai-7b660';
    }

    public function index(Request $request)
    {
        $query = Announcement::query()->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $announcements = $query->paginate(20);
        return view('admin.announcement.index', compact('announcements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,event',
            'status' => 'required|in:draft,published,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'send_push' => 'boolean'
        ]);

        $announcement = Announcement::create(array_merge($data, ['created_by'=>auth()->id()]));

        if($announcement->send_push){
            // SendPushNotificationJob::dispatch($announcement);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'お知らせを作成しました',
                'data' => $announcement,
            ], 201);
        }
        return redirect()->route('announcements.index')->with('success', 'お知らせを作成しました');
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id); // IDで取得
    
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,event',
            'status' => 'required|in:draft,published,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'send_push' => 'boolean'
        ]);
    
        $announcement->update($data);
    
        if($announcement->send_push){
            // SendPushNotificationJob::dispatch($announcement);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'お知らせを更新しました',
                'data' => $announcement->fresh(),
            ]);
        }
        return redirect()->route('announcements.index')
                         ->with('success','お知らせを更新しました');
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id); // IDで取得
        $announcement->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'お知らせを削除しました',
            ]);
        }
        return redirect()->route('announcements.index')
                         ->with('success','お知らせを削除しました');
    }

    public function sendPushNotification()
    {
        $query = Announcement::query();

        $query->where('status', 'published');

        // Get announcements that are currently active based on start_date and end_date
        $now = Carbon::now();
        $query->where(function ($q) use ($now) {
            $q->whereNull('start_date')
              ->orWhere('start_date', '<=', $now);
        });
        $query->where(function ($q) use ($now) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', $now);
        });

        $announcements = $query->get();
        if ($announcements->isEmpty()) {
            return response()->json([
                'message' => '現在、送信予定の通知は存在しません。',
                'count' => 0,
            ]);
        }
        $totalNotificationsSent = 0;
        $errors = [];

        // Fetch all unique and valid device tokens once to avoid repeated DB queries
        $allDeviceTokens = User::whereNotNull('fcm_device_token')
                               ->where('fcm_device_token', '!=', '')
                               ->pluck('fcm_device_token')
                               ->unique()
                               ->toArray();

        if (empty($allDeviceTokens)) {
            Log::info('No valid device tokens found. Skipping push notifications.');
            return response()->json([
                'message' => 'No device tokens found, no notifications sent.',
                'count' => 0,
            ]);
        }
        
        // Get the access token ONCE for all announcements and tokens
        try {
            $accessToken = $this->accessTokenService->getAccessToken();
        } catch (Exception $e) {
            Log::error('Firebase authentication failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to authenticate with Firebase.',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Loop through each active announcement
        foreach ($announcements as $announcement) {
            // Firebase Cloud Messaging HTTP v1 API endpoint
            $url = "https://fcm.googleapis.com/v1/projects/{$this->firebaseProjectId}/messages:send";

            // Notification payload for the user's device
            $notification = [
                'title' => $announcement->title,
                'body' => $announcement->content,
            ];

            // Custom data payload (values must be strings for FCM data messages)
            $dataPayload = [
                'announcement_id' => (string)$announcement->id,
                'type' => (string)$announcement->type,
            ];

            // Headers for the cURL request
            $headers = [
                'Authorization: Bearer ' . $accessToken, // New Authorization with Bearer token
                'Content-Type: application/json',
            ];

            // --- CRITICAL EFFICIENCY NOTE ---
            // The FCM HTTP v1 API's messages:send endpoint is for a single message.
            // To send one announcement to multiple individual device tokens via direct cURL,
            // you must iterate and send a separate request for each token.
            // For a large number of tokens, this can be slow and inefficient.
            // Consider using FCM Topics for broadcast messages or a dedicated PHP FCM library for batching.

            foreach ($allDeviceTokens as $deviceToken) {
                $fields = [
                    'message' => [
                        'token' => $deviceToken, // Target a specific device token
                        'notification' => $notification,
                        'data' => $dataPayload, // Custom data accessible in your app
                        'android' => [
                            'ttl' => '3600s', // 1時間有効
                            'priority' => 'high',
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10', // 即時送信
                                'apns-expiration' => (string)(time() + 3600), // 1時間後まで有効
                            ],
                        ],
                    ]
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
                $curlError = curl_error($ch); // Get any cURL error message
                curl_close($ch);

                if ($result === false) {
                    $errorMsg = "cURL Error for token {$deviceToken} (Announcement ID: {$announcement->id}): {$curlError}";
                    $errors[] = $errorMsg;
                    Log::error($errorMsg);
                } else {
                    $decodedResult = json_decode($result, true);
                    if ($httpCode !== 200) {
                        $errorMsg = "FCM API Error for token {$deviceToken} (Announcement ID: {$announcement->id}, HTTP {$httpCode}): " . json_encode($decodedResult);
                        $errors[] = $errorMsg;
                        Log::warning($errorMsg); // Use warning for API-level errors
                        // You should inspect $decodedResult for specific FCM error codes
                        // e.g., if a token is invalid, delete it from your database
                        // if (isset($decodedResult['error']['details'][0]['errorCode']) && $decodedResult['error']['details'][0]['errorCode'] === 'UNREGISTERED') {
                        //     // Optionally, delete the invalid token from your User table
                        // }
                    } else {
                        // Successfully sent notification to this token
                        $totalNotificationsSent++;
                    }
                }
            } // End of foreach $allDeviceTokens

            // Remove `die;` - it was stopping execution prematurely.
            // The original code changes status here. This means an announcement is marked 'archived'
            // even if some individual notifications failed to send. Adjust this logic if needed.
            if ($announcement->status === 'published') { // Assuming 'published' is a status, maybe 'pending' for first send
                $announcement->status = 'archived'; // Or 'sent'
                $announcement->save();
            }
        } // End of foreach $announcements

        $responseMessage = 'Push notifications process completed.';
        if (!empty($errors)) {
            $responseMessage .= ' Some errors occurred during sending.';
            Log::warning('FCM Notification Process completed with errors: ' . count($errors) . ' errors found.');
        }

        return response()->json([
            'message' => '通知を送信しました',
            'total_announcements_processed' => $announcements->count(),
            'total_notifications_attempted_to_send' => $announcements->count() * count($allDeviceTokens),
            'successful_notifications_sent_count' => $totalNotificationsSent,
            'errors_count' => count($errors),
            'errors_sample' => array_slice($errors, 0, 5), // Show a sample of errors
        ]);
    }
    public function getAnnouncements()
    {
        $query = Announcement::query();

        $query->where('status', 'published');

        // Get announcements that are currently active based on start_date and end_date
        $now = Carbon::now();
        $query->where(function ($q) use ($now) {
            $q->whereNull('start_date')
              ->orWhere('start_date', '<=', $now);
        });
        $query->where(function ($q) use ($now) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', $now);
        });

        $announcements = $query->get();
        
        return response()->json($announcements);
    }
}