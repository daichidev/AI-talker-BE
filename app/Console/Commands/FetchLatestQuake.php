<?php
// app/Console/Commands/FetchLatestQuake.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Earthquake;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\GoogleAccessTokenService;
use Exception; // For catching token generation errors

class FetchLatestQuake extends Command
{
    protected $signature = 'quake:fetch-latest';
    protected $description = 'P2PQuake API から最新の地震情報(code=551)を取得して保存する';
    protected string $firebaseProjectId = 'myai-7b660';
    protected GoogleAccessTokenService $accessTokenService;

    private const API_URL = 'https://api.p2pquake.net/v2/history?codes=551&limit=1';

    public function handle(): int
    {
        $this->info('Fetching latest earthquake info from P2PQuake...');
        Log::info('Fetching latest earthquake info from P2PQuake...');
        try {
            $res = Http::timeout(10)->get(self::API_URL);

            if (!$res->ok()) {
                $this->error('HTTP error: ' . $res->status());
                return self::FAILURE;
            }

            $items = $res->json();

            if (!is_array($items) || count($items) === 0) {
                $this->warn('No earthquake data returned.');
                return self::SUCCESS;
            }

            $item = $items[0];
            $eq = $item['earthquake'] ?? [];
            $hypo = $eq['hypocenter'] ?? [];

            // === JS の関数を PHP に移植 ===
            $reportedAt = $this->parseTime($item['time'] ?? null);
            $occurredAt = $this->parseTime($eq['time'] ?? ($item['time'] ?? null));

            $mag = $eq['magnitude'] ?? null;
            $depth = $hypo['depth'] ?? null;
            $maxScale = $eq['maxScale'] ?? null;
            $maxScaleLabel = $this->maxScaleLabel($maxScale);
            $tsunamiCode = $eq['domesticTsunami'] ?? null;
            $tsunamiLabel = $this->tsunamiLabel($tsunamiCode);

            $lat = $hypo['latitude'] ?? null;
            $lon = $hypo['longitude'] ?? null;
            $count = Earthquake::where('external_id', $item['id'])->count();
            if ($count > 0) {
                Log::info('Quake already exists: external_id=' . $item['id']);
                return self::SUCCESS;
            }
            // push notification
            $notification = [
                'title' => '地震情報',
                'body' => "地震情報が更新されました。\n震度{$maxScaleLabel}、{$tsunamiLabel}、{$mag}マグニチュード、{$depth}km深さ",
            ];

            // Custom data payload (values must be strings for FCM data messages)
            $dataPayload = [
                'announcement_id' => (string)$item['id'],
                'type' => "warning",
            ];
            $this->accessTokenService = new GoogleAccessTokenService();
            try {
                $accessToken = $this->accessTokenService->getAccessToken();
            } catch (Exception $e) {
                Log::error('Firebase authentication failed: ' . $e->getMessage());
            }

            // Headers for the cURL request
            $headers = [
                'Authorization: Bearer ' . $accessToken, // New Authorization with Bearer token
                'Content-Type: application/json',
            ];
            $url = "https://fcm.googleapis.com/v1/projects/{$this->firebaseProjectId}/messages:send";
            $allDeviceTokens = User::whereNotNull('fcm_device_token')
                ->where('fcm_device_token', '!=', '')
                ->pluck('fcm_device_token')
                ->unique()
                ->toArray();

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
                    $errorMsg = "cURL Error for token {$deviceToken}: {$curlError}";
                    $errors[] = $errorMsg;
                    Log::error($errorMsg);
                } else {
                    $decodedResult = json_decode($result, true);
                    if ($httpCode !== 200) {
                        $errorMsg = "FCM API Error for token {$deviceToken} (HTTP {$httpCode}): " . json_encode($decodedResult);
                        $errors[] = $errorMsg;
                        Log::warning($errorMsg);
                    }
                }
            } // End of foreach $allDeviceTokens

            // DB に保存（同じ external_id があれば更新）
            $quake = Earthquake::updateOrCreate(
                ['external_id' => $item['id'] ?? ''],
                [
                    'version'          => $item['ver'] ?? null,
                    'reported_at'      => $reportedAt,
                    'occurred_at'      => $occurredAt,
                    'hypocenter_name'  => $hypo['name'] ?? null,
                    'latitude'         => $lat,
                    'longitude'        => $lon,
                    'depth_km'         => $depth,
                    'magnitude'        => $mag,
                    'max_scale'        => $maxScale,
                    'max_scale_label'  => $maxScaleLabel,
                    'tsunami_code'     => $tsunamiCode,
                    'tsunami_label'    => $tsunamiLabel,
                    'raw'              => $item,
                ]
            );

            $this->info(
                'Saved quake: external_id=' . $quake->external_id .
                    ' M' . $quake->magnitude . ' maxScale=' . $quake->max_scale_label
            );

            // ここで「通知」「LINE」「メール」「WebSocket配信」などに繋げてもOK

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Exception: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * JS の formatJstTime 相当
     * P2PQuake は "YYYY/MM/DD hh:mm:ss" のこともあるので両対応
     */
    private function parseTime(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        // "YYYY/MM/DD hh:mm:ss" 形式
        if (str_contains($value, '/') && !str_contains($value, 'T')) {
            // そのまま日本時間としてパース
            try {
                return Carbon::createFromFormat('Y/m/d H:i:s', $value, 'Asia/Tokyo')
                    ->setTimezone('UTC'); // DB は UTC で保存したい場合
            } catch (\Throwable $e) {
                return null;
            }
        }

        // ISO 形式などは Carbon に任せる
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * JS の maxScaleLabel 相当
     */
    private function maxScaleLabel($scale): string
    {
        if ($scale === null || $scale < 0) {
            return '不明';
        }

        $map = [
            0  => '0',
            10 => '1',
            20 => '2',
            30 => '3',
            40 => '4',
            45 => '5弱',
            50 => '5強',
            55 => '6弱',
            60 => '6強',
            70 => '7',
        ];

        return $map[$scale] ?? ('code ' . $scale);
    }

    /**
     * JS の tsunamiLabel 相当
     */
    private function tsunamiLabel(?string $code): string
    {
        if (!$code) return '不明';
        return match ($code) {
            'None'         => 'なし',
            'Unknown'      => '不明',
            'Checking'     => '調査中',
            'NonEffective' => '若干の海面変動',
            'Warning'      => '津波警報級',
            default        => $code,
        };
    }
}
