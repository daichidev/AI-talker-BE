<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CampaignDiscount;

class CampaignDiscountApiController extends Controller
{
    public function today()
    {
        $discount = CampaignDiscount::activeNow()
            ->orderByDesc('discount_percent') // 複数あれば一番お得なもの
            ->first();

        if (!$discount) {
            return response()->json([
                'active' => false,
                'discount' => null,
            ]);
        }

        return response()->json([
            'active' => true,
            'discount' => [
                'id'               => $discount->id,
                'title'            => $discount->title,
                'description'      => $discount->description,
                'discount_percent' => $discount->discount_percent,
                'starts_at'        => $discount->starts_at->toIso8601String(),
                'ends_at'          => $discount->ends_at->toIso8601String(),
                'image_url'        => $discount->banner_url,
            ],
        ]);
    }
}
