<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisasterFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeojsonController extends Controller
{
    // 画面表示（ボタンだけ）
    public function show()
    {
        return view('admin.geojson-import');
    }

    // 実際のインポート処理
    public function run(Request $request)
    {    // ★ 実行時間制限を解除（または延長）
        ini_set('max_execution_time', '0'); // 0 = 無制限
        set_time_limit(0);                  // 念のため

        DisasterFacility::truncate();
        $log[] = "  ✅ Deleted all disaster facilities.";

        $files = [
            "47000_2","47000_1","46000_2","46000_1","45000_2","45000_1",
            "44000_2","44000_1","43000_2","43000_1","42000_2","42000_1",
            "41000_2","41000_1","40000_2","40000_1","39000_2","39000_1",
            "38000_2","38000_1","37000_2","37000_1","36000_2","36000_1",
            "35000_2","35000_1","34000_2","34000_1","33000_2","33000_1",
            "32000_2","32000_1","31000_2","31000_1","30000_2","30000_1",
            "29000_2","29000_1","28000_2","28000_1","27000_2","27000_1",
            "26000_2","26000_1","25000_2","25000_1","24000_2","24000_1",
            "23000_2","23000_1","22000_2","22000_1","21000_2","21000_1",
            "20000_2","20000_1","19000_2","19000_1","18000_2","18000_1",
            "17000_2","17000_1","16000_2","16000_1","15000_2","15000_1",
            "14000_2","14000_1","13000_2","13000_1","12000_2","12000_1",
            "11000_2","11000_1","10000_2","10000_1","09000_2","09000_1",
            "08000_2","08000_1","07000_2","07000_1","06000_2","06000_1",
            "05000_2","05000_1","04000_2","04000_1","03000_2","03000_1",
            "02000_2","02000_1","01000_2","01000_1",
        ];

        $baseUrl = 'https://hinanmap.gsi.go.jp/hinanjocp/defaultFtpData/geoJSON';

        $log = [];
        $totalInserted = 0;

        foreach ($files as $fileCode) {
            $url = "{$baseUrl}/{$fileCode}.geojson";
            $log[] = "Downloading: {$url}";

            try {
                $response = Http::timeout(30)->get($url);

                if (!$response->ok()) {
                    $log[] = "  ❌ Failed: HTTP " . $response->status();
                    continue;
                }

                $geojson = $response->json();

                if (!isset($geojson['features']) || !is_array($geojson['features'])) {
                    $log[] = "  ❌ Invalid GeoJSON format";
                    continue;
                }
                $seenInThisFile = []; // 同じファイル内の緯度経度重複チェック

                foreach ($geojson['features'] as $feature) {
                    $geometry   = $feature['geometry']   ?? null;
                    $properties = $feature['properties'] ?? [];

                    // Point 以外はスキップ
                    if (!$geometry || ($geometry['type'] ?? '') !== 'Point') {
                        continue;
                    }

                    $coords = $geometry['coordinates'] ?? null;
                    if (!is_array($coords) || count($coords) < 2) {
                        continue;
                    }

                    $longitude = $coords[0];
                    $latitude  = $coords[1];
                    // --- ① このファイルの中で緯度経度が重複していたらスキップ ---
                    $key = $latitude . '_' . $longitude;
                    if (isset($seenInThisFile[$key])) {
                        continue;
                    }
                    $seenInThisFile[$key] = true;
                    $row = [
                        'longitude'  => $longitude,
                        'latitude'   => $latitude,
                        'properties' => $properties,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    DisasterFacility::create($row);
                    $totalInserted++;
                    $log[] = "  ✅ Inserted 1 row.";
                }
            } catch (\Throwable $e) {
                $log[] = "  💥 Error: ".$e->getMessage();
            }
        }

        return view('admin.geojson-import', [
            'log' => $log,
            'totalInserted' => $totalInserted,
        ]);
    }

    public function getNearbyDisasterFacilities(Request $request)
    {
        // ★ 現在位置（緯度・経度）をリクエストから受け取る想定
        $lat = (float) $request->input('lat');  // 例: 35.681236
        $lng = (float) $request->input('lng');  // 例: 139.767125
        $radius = (int) $request->input('radius'); // メートル

        // 地球半径（メートル）
        $earthRadius = 6371000;

        // Haversine 式 を使って距離(m)を計算して絞り込み
        $disasterFacilities = DisasterFacility::selectRaw("
                disaster_facilities.*,
                (
                    $earthRadius * acos(
                        cos(radians(?)) * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(latitude))
                    )
                ) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)  // 1000m 以内
            ->orderBy('distance')                // 近い順
            ->get();

        return response()->json($disasterFacilities);
    }
}
