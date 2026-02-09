<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JmaOfficeResolverService
{
    private const XY_URL = 'https://www.jma.go.jp/bosai/common/const/xy.json';

    private const AREA_URL = 'https://www.jma.go.jp/bosai/common/const/area.json';

    /**
     * 緯度・経度から最適な class10（children）を 1 件取得（TS fetchBestChild 相当）
     *
     * @return array{code: string, name: string, distance: float}|null
     */
    public function fetchBestChild(float $latitude, float $longitude): ?array
    {
        try {
            $area = $this->fetchArea();
            $xy = $this->fetchXy();

            $nearestClass20 = $this->findNearestClass20($area, $xy, $latitude, $longitude);
            if ($nearestClass20 === null) {
                return null;
            }

            $parentInfo = $this->getOfficeAndClass10OfClass20($area, $nearestClass20['code']);
            if ($parentInfo === null) {
                Log::warning('JMA: class20 の親 office/class10 を特定できませんでした', [
                    'class20' => $nearestClass20['code'],
                ]);
                return null;
            }

            $officeCode = $parentInfo['officeCode'];
            $class10Children = $this->getClass10ChildrenOfOffice($area, $officeCode);
            
            Log::warning('+++++++++++++++++++++++++++++++++++++ ', $class10Children);
            $bestChild = null;
            $bestChildDist = PHP_FLOAT_MAX;

            foreach ($class10Children as $child) {
                $d = $this->getMinDistanceOfClass10(
                    $area,
                    $xy,
                    $child['code'],
                    $latitude,
                    $longitude
                );
                if ($d < $bestChildDist) {
                    $bestChildDist = $d;
                    $bestChild = [
                        'code'     => $child['code'],
                        'name'     => $child['data']['name'] ?? '',
                        'distance' => $d,
                    ];
                }
            }

            return $bestChild;
        } catch (\Throwable $e) {
            Log::error('JMA fetchBestChild error', [
                'lat' => $latitude,
                'lon' => $longitude,
                'msg' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Haversine 距離（メートル）
     */
    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371e3;
        $toRad = fn ($d) => $d * M_PI / 180.0;
        $phi1 = $toRad($lat1);
        $phi2 = $toRad($lat2);
        $deltaPhi = $toRad($lat2 - $lat1);
        $deltaLambda = $toRad($lon2 - $lon1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * (sin($deltaLambda / 2) ** 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }

    private function fetchJson(string $url): array
    {
        $response = Http::timeout(15)->retry(3, 1000)->get($url);
        if (!$response->successful()) {
            throw new \RuntimeException("{$url} の取得に失敗: " . $response->status());
        }
        $data = $response->json();
        return is_array($data) ? $data : [];
    }

    private function fetchArea(): array
    {
        return $this->fetchJson(self::AREA_URL);
    }

    private function fetchXy(): array
    {
        return $this->fetchJson(self::XY_URL);
    }

    /**
     * 現在地に最も近い class20 を 1 件取得（hoppo は除外）
     *
     * @return array{code: string, lat: float, lon: float, name: string, distance: float}|null
     */
    private function findNearestClass20(array $area, array $xy, float $curLat, float $curLon): ?array
    {
        $class20s = $xy['class20s'] ?? [];
        if (empty($class20s)) {
            throw new \RuntimeException('xy.json に class20s がありません');
        }

        $areaClass20s = $area['class20s'] ?? [];
        $best = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach ($class20s as $code => $coord) {
            if ($code === 'hoppo' || !is_array($coord) || count($coord) < 2) {
                continue;
            }
            [$lat, $lon] = array_map('floatval', array_slice($coord, 0, 2));
            $d = $this->distanceMeters($curLat, $curLon, $lat, $lon);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = [
                    'code'     => $code,
                    'lat'      => $lat,
                    'lon'      => $lon,
                    'name'     => $areaClass20s[$code]['name'] ?? '(name unknown)',
                    'distance' => $d,
                ];
            }
        }

        return $best;
    }

    /**
     * class20 → class15 → class10 → office をたどって office と class10 を返す
     *
     * @return array{officeCode: string, office: array, class10Code: string, class10: array}|null
     */
    private function getOfficeAndClass10OfClass20(array $area, string $class20Code): ?array
    {
        $c20 = $area['class20s'][$class20Code] ?? null;
        if ($c20 === null) {
            return null;
        }

        $class15Code = $c20['parent'] ?? null;
        if ($class15Code === null) {
            return null;
        }
        $c15 = $area['class15s'][$class15Code] ?? null;
        if ($c15 === null) {
            return null;
        }

        $class10Code = $c15['parent'] ?? null;
        if ($class10Code === null) {
            return null;
        }
        $c10 = $area['class10s'][$class10Code] ?? null;
        if ($c10 === null) {
            return null;
        }

        $officeCode = $c10['parent'] ?? null;
        if ($officeCode === null) {
            return null;
        }
        $office = $area['offices'][$officeCode] ?? null;
        if ($office === null) {
            return null;
        }

        return [
            'officeCode'   => $officeCode,
            'office'       => $office,
            'class10Code'  => $class10Code,
            'class10'      => $c10,
        ];
    }

    /**
     * ある office 配下の class10 一覧
     *
     * @return array<int, array{code: string, data: array}>
     */
    private function getClass10ChildrenOfOffice(array $area, string $officeCode): array
    {
        $office = $area['offices'][$officeCode] ?? null;
        if ($office === null) {
            return [];
        }

        $class10Ids = $office['children'] ?? [];
        $result = [];
        foreach ($class10Ids as $code) {
            $data = $area['class10s'][$code] ?? null;
            if ($data !== null) {
                $result[] = ['code' => $code, 'data' => $data];
            }
        }
        return $result;
    }

    /**
     * class10 配下の class20 との最短距離（メートル）
     */
    private function getMinDistanceOfClass10(
        array $area,
        array $xy,
        string $class10Code,
        float $curLat,
        float $curLon
    ): float {
        $c10 = $area['class10s'][$class10Code] ?? null;
        if ($c10 === null) {
            return PHP_FLOAT_MAX;
        }

        $class15Ids = $c10['children'] ?? [];
        $class15s = $area['class15s'] ?? [];
        $xyClass20s = $xy['class20s'] ?? [];
        $bestDist = PHP_FLOAT_MAX;

        foreach ($class15Ids as $class15Code) {
            $c15 = $class15s[$class15Code] ?? null;
            if ($c15 === null) {
                continue;
            }
            $class20Ids = $c15['children'] ?? [];
            foreach ($class20Ids as $c20Code) {
                $coord = $xyClass20s[$c20Code] ?? null;
                if (!is_array($coord) || count($coord) < 2) {
                    continue;
                }
                [$lat, $lon] = array_map('floatval', array_slice($coord, 0, 2));
                $d = $this->distanceMeters($curLat, $curLon, $lat, $lon);
                if ($d < $bestDist) {
                    $bestDist = $d;
                }
            }
        }

        return $bestDist;
    }
}
