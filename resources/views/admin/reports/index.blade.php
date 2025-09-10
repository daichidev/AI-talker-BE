@extends('layouts.admin')

@section('title', '報告一覧')

@section('content')
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">報告一覧</h2>
                        <form action="{{ route('admin.reports.index') }}" method="GET" class="w-full max-w-md">
                            <div class="flex gap-4">
                            <div class="flex-1">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                    placeholder="メールアドレスで検索..."
                                    class="mt-1 p-2 block w-full rounded-md !border-blue-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                検索
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.reports.index') }}" 
                                    class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    クリア
                                </a>
                            @endif
                        </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名前</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メール</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">種類</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">テキスト</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作成日</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($reports as $report)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $report->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ optional($report->user)->name ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ optional($report->user)->email ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ match($report->report_type) {
                                                0 => '不適切なコンテンツの報告',
                                                1 => 'バグ報告',
                                                2 => '運営へのご意見・ご要望',
                                                default => 'その他',
                                            } }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Illuminate\Support\Str::limit($report->report_text, 30) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $report->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button" onclick="openDetailModal({ id: '{{ $report->id }}', user: '{{ optional($report->user)->email ?? '' }}', type: `{{ match($report->report_type) {
                                                0 => '不適切なコンテンツの報告',
                                                1 => 'バグ報告',
                                                2 => '運営へのご意見・ご要望',
                                                default => 'その他',
                                            } }}`, name:'{{ optional($report->user)->name ?? '' }}', text: '{{$report->report_text}}' })" class="text-indigo-600 hover:text-indigo-900">詳細</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $reports->withQueryString()->links() }}
                    </div>
                </div>
            </div>
            <div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">報告詳細</h3>
                        <button onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-700">×</button>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="grid grid-cols-3 gap-8"><span class="text-gray-500 text-right">名前:</span> <span id="detail-name" class="font-bold col-span-2"></span></div>
                        <div class="grid grid-cols-3 gap-8"><span class="text-gray-500 text-right">メール:</span> <span id="detail-email" class="font-bold col-span-2"></span></div>
                        <div class="grid grid-cols-3 gap-8"><span class="text-gray-500 text-right">種類:</span> <span id="detail-type" class="font-bold col-span-2"></span></div>
                        <div>
                            <div class="text-gray-500 mb-1">テキスト:</div>
                            <div id="detail-text" class="whitespace-pre-wrap break-words p-3 bg-gray-50 rounded-md text-gray-800"></div>
                        </div>
                    </div>
                    <div class="mt-6 text-right">
                        <button onclick="closeDetailModal()" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">閉じる</button>
                    </div>
                </div>
            </div>
@endsection

@push('scripts')
    <script>
        function openDetailModal(data) {
            document.getElementById('detail-name').textContent = data.name;
            document.getElementById('detail-email').textContent = data.user;
            document.getElementById('detail-type').textContent = data.type;
            document.getElementById('detail-text').textContent = data.text;

            const modal = document.getElementById('detail-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeDetailModal() {
            const modal = document.getElementById('detail-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
@endpush


