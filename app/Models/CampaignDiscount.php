<?php
// app/Models/CampaignDiscount.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'discount_percent',
        'starts_at',
        'ends_at',
        'banner_path',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    // 今有効なキャンペーン
    public function scopeActiveNow($query)
    {
        $now = now(); // config('app.timezone') ベース
        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    // 画像URLを返すアクセサ
    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner_path) return null;
        return \Storage::url($this->banner_path);
    }
}
