<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CampaignDiscountController extends Controller
{
    /**
     * キャンペーン一覧
     */
    public function index(Request $request)
    {
        $query = CampaignDiscount::query();

        // 検索（タイトル / 説明）
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // 並び順：開始日が新しい順 → ID降順のイメージ
        $campaigns = $query
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.campaign-discounts.index', compact('campaigns'));
    }

    /**
     * 新規作成
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'starts_at'        => ['nullable', 'date'],
            'ends_at'          => ['nullable', 'date', 'after_or_equal:starts_at'],
            'banner'           => ['nullable', 'image', 'max:2048'],
            'is_active'        => ['sometimes', 'boolean'],
        ]);

        // 日付文字列 → Carbon（nullable）
        $data['starts_at'] = $data['starts_at'] ?? null
            ? Carbon::parse($data['starts_at'])
            : null;

        $data['ends_at'] = $data['ends_at'] ?? null
            ? Carbon::parse($data['ends_at'])
            : null;

        // 画像アップロード
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('campaign_banners', 'public');
        }

        // チェックボックス
        $data['is_active'] = $request->boolean('is_active');

        CampaignDiscount::create($data);

        return redirect()
            ->route('admin.campaign-discounts.index')
            ->with('success', '割引キャンペーンを作成しました。');
    }

    /**
     * 更新
     */
    public function update(Request $request, int $id)
    {
        $campaign = CampaignDiscount::findOrFail($id);

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'starts_at'        => ['nullable', 'date'],
            'ends_at'          => ['nullable', 'date', 'after_or_equal:starts_at'],
            'banner'           => ['nullable', 'image', 'max:2048'],
            'is_active'        => ['sometimes', 'boolean'],
        ]);

        $data['starts_at'] = $data['starts_at'] ?? null
            ? Carbon::parse($data['starts_at'])
            : null;

        $data['ends_at'] = $data['ends_at'] ?? null
            ? Carbon::parse($data['ends_at'])
            : null;

        // 画像再アップロード時は古いものを削除
        if ($request->hasFile('banner')) {
            if ($campaign->banner_path && Storage::disk('public')->exists($campaign->banner_path)) {
                Storage::disk('public')->delete($campaign->banner_path);
            }
            $data['banner_path'] = $request->file('banner')->store('campaign_banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $campaign->update($data);

        return redirect()
            ->route('admin.campaign-discounts.index')
            ->with('success', '割引キャンペーンを更新しました。');
    }

    /**
     * 削除
     */
    public function destroy(int $id)
    {
        $campaign = CampaignDiscount::findOrFail($id);

        // 画像があれば削除
        if ($campaign->banner_path && Storage::disk('public')->exists($campaign->banner_path)) {
            Storage::disk('public')->delete($campaign->banner_path);
        }

        $campaign->delete();

        return redirect()
            ->route('admin.campaign-discounts.index')
            ->with('success', '割引キャンペーンを削除しました。');
    }
}
