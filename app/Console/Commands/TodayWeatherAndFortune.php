<?php

namespace App\Console\Commands;

use App\Services\JmaOfficeResolverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TodayWeatherAndFortune extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:today-weather-and-fortune
                            {--lat= : 緯度（指定時のみ fetchBestChild で children code 取得）}
                            {--lon= : 経度}';
    protected $description = 'Send today weather and fortune';

    public function __construct(
        private JmaOfficeResolverService $jmaOfficeResolver
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lat = 43.219915;
        $lon = 141.593962;

        if ($lat !== null && $lon !== null) {
            $bestChild = $this->jmaOfficeResolver->fetchBestChild($lat, $lon);
            if ($bestChild !== null) {
                Log::info('JMA fetchBestChild result', [
                    'children_code' => $bestChild['code'],
                    'name'          => $bestChild['name'],
                    'distance_m'    => round($bestChild['distance'], 2),
                ]);
                $this->info("Children code: {$bestChild['code']} ({$bestChild['name']})");
            } else {
                Log::warning('JMA fetchBestChild returned null', ['lat' => $lat, 'lon' => $lon]);
            }
        }

        return self::SUCCESS;
    }
}
