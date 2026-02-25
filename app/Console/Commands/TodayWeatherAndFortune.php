<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\Temperature;
use App\Models\User;
use App\Models\Weather;
use App\Services\FortuneService;
use App\Services\GoogleAccessTokenService;
use App\Services\JmaOfficeResolverService;
use App\Services\ZodiacService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TodayWeatherAndFortune extends Command
{
    protected $signature = 'app:today-weather-and-fortune';
    protected $description = 'Send today weather and fortune';

    private const FIREBASE_PROJECT_ID = 'myai-7b660';

    public function __construct(
        private JmaOfficeResolverService $jmaOfficeResolver,
        private FortuneService $fortuneService,
        private ZodiacService $zodiacService,
        private GoogleAccessTokenService $accessTokenService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $accessToken = null;
        try {
            $accessToken = $this->accessTokenService->getAccessToken();
        } catch (Exception $e) {
            Log::error('TodayWeatherAndFortune: Firebase auth failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        User::all()->each(function (User $user) use ($accessToken) {
            $location = json_decode($user->location, true);
            if ($location === null) {
                return;
            }
            $lat = $location['lat'] ?? null;
            $lon = $location['lng'] ?? null;
            if ($lat === null || $lon === null) {
                return;
            }
            $bestChild = $this->jmaOfficeResolver->fetchBestChild($lat, $lon);
            if ($bestChild === null) {
                return;
            }
            $weather = Weather::where('class10_code', $bestChild['code'])->first();
            $temperature = Temperature::where('class10_code', $bestChild['code'])->first();
            if ($weather === null || $temperature === null) {
                return;
            }
            $profile = Profile::where('user_id', $user->id)->first();
            if ($profile === null || $profile->blood_type === null) {
                return;
            }

            $fortune = $this->fortuneService->generateFortune($profile->blood_type);
            $dailyHoroscope = null;
            if ($profile->birthdate !== null) {
                $zodiacSign = $this->zodiacService->getZodiacSign($profile->birthdate);
                $dailyHoroscope = $this->zodiacService->getDailyHoroscope($zodiacSign);
            }

            $message = $this->buildPushMessage(
                $profile,
                $weather->weather,
                (int) $temperature->min_temperature,
                (int) $temperature->max_temperature,
                $fortune,
                $dailyHoroscope
            );

            $deviceToken = $user->fcm_device_token ?? null;
            if ($deviceToken !== null && $deviceToken !== '') {
                $sent = $this->sendFcmNotification(
                    $accessToken,
                    $deviceToken,
                    '今日の天気と運勢',
                    $message,
                    ['type' => 'today_weather_fortune']
                );
                if ($sent) {
                    Log::info('TodayWeatherAndFortune: push sent', ['user_id' => $user->id]);
                } else {
                    Log::warning('TodayWeatherAndFortune: push failed', ['user_id' => $user->id]);
                }
            } else {
                Log::debug('TodayWeatherAndFortune: no FCM token', ['user_id' => $user->id]);
            }
        });

        return self::SUCCESS;
    }

    /**
     * Send one FCM notification. Returns true on HTTP 200, false otherwise.
     */
    private function sendFcmNotification(
        string $accessToken,
        string $deviceToken,
        string $title,
        string $body,
        array $dataPayload = []
    ): bool {
        $dataPayload = array_map(fn ($v) => (string) $v, $dataPayload);
        $url = 'https://fcm.googleapis.com/v1/projects/' . self::FIREBASE_PROJECT_ID . '/messages:send';
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
        $fields = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $dataPayload,
                'android' => [
                    'ttl' => '86400s',
                    'priority' => 'high',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                        'apns-expiration' => (string) (time() + 86400),
                    ],
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            Log::error('TodayWeatherAndFortune FCM cURL error', ['error' => $curlError]);

            return false;
        }

        if ($httpCode !== 200) {
            Log::warning('TodayWeatherAndFortune FCM API error', [
                'http_code' => $httpCode,
                'response' => json_decode($result, true),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Build a push-notification friendly message (weather + blood-type fortune + zodiac).
     *
     * @param array{message: string, luckyColorName: string, luckyItem: string, overall: int} $fortune
     * @param array{message: string, luckyColor: string, luckyItem: string, sign: string} | null $dailyHoroscope
     */
    private function buildPushMessage(
        Profile $profile,
        string $weatherText,
        int $minTemp,
        int $maxTemp,
        array $fortune,
        ?array $dailyHoroscope
    ): string {
        $name = trim((string) $profile->name);
        $greeting = $name !== '' ? "{$name}さん、今日の天気と運勢です" : '今日の天気と運勢です';
        $weatherLine = "☀️ 天気：{$weatherText}（{$minTemp}〜{$maxTemp}℃）";

        $bloodLabel = $profile->blood_type !== '' ? "{$profile->blood_type}" : '血液型';
        $fortuneBlock = "🩸 {$bloodLabel}の運勢（★{$fortune['overall']}/5）\n{$fortune['message']}\nラッキー：{$fortune['luckyColorName']} / {$fortune['luckyItem']}";

        $parts = [$greeting, '', $weatherLine, '', $fortuneBlock];

        if ($dailyHoroscope !== null) {
            $zodiacName = ZodiacService::getZodiacSignName($dailyHoroscope['sign']);
            $zodiacBlock = "☆ {$zodiacName}の運勢（★{$dailyHoroscope['total']}/5）\n{$dailyHoroscope['message']}\nラッキー：{$dailyHoroscope['luckyColor']} / {$dailyHoroscope['luckyItem']}";
            $parts[] = '';
            $parts[] = $zodiacBlock;
        }

        return implode("\n", $parts);
    }
}
