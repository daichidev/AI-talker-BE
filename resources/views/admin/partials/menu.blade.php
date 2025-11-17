<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6 h-12">
            <a href="{{ route('admin.users.index') }}"
               class="{{ request()->routeIs('admin.users.*') ? 'text-blue-700 font-bold border-b-2 border-blue-700' : 'text-blue-500 hover:text-blue-700' }}">
                ユーザー
            </a>
            <a href="{{ route('admin.reports.index') }}"
               class="{{ request()->routeIs('admin.reports.*') ? 'text-blue-700 font-bold border-b-2 border-blue-700' : 'text-blue-500 hover:text-blue-700' }}">
                報告
            </a>
            <a href="{{ route('admin.announcement.index') }}"
               class="{{ request()->routeIs('admin.announcement.*') ? 'text-blue-700 font-bold border-b-2 border-blue-700' : 'text-blue-500 hover:text-blue-700' }}">
               お知らせ
            </a>
            <a href="{{ route('admin.geojson.import.show') }}"
               class="{{ request()->routeIs('admin.geojson.import.*') ? 'text-blue-700 font-bold border-b-2 border-blue-700' : 'text-blue-500 hover:text-blue-700' }}">
               避難所・指定避難所データ インポート
            </a>
        </div>
    </div>
</div>
