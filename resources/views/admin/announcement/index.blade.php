@extends('layouts.admin')

@section('title', 'お知らせ')

@section('content')
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-gray-100">
                <div class="px-4 py-5 sm:p-6">
                    <div class="mb-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">お知らせ一覧</h2>
                            <button type="button" data-modal-target="create" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/></svg>
                                新規作成
                            </button>
                        </div>
                        <form action="{{ route('admin.announcement.index') }}" method="GET" class="w-full">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="relative flex-1">
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="キーワードで検索（タイトル/本文/作成者）"
                                           class="block w-full rounded-md border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                        検索
                                    </button>
                                    @if(request('search'))
                                        <a href="{{ route('admin.announcement.index') }}"
                                           class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                            クリア
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                    @if(session('success'))
                    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                    @endif
                    <div class="overflow-x-auto rounded-md ring-1 ring-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">タイトル</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">本文</th> 
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">タイプ</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">ステータス</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">公開期間</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">作成者</th>
                                    <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">送信済み</th> -->
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($announcements as $announcement)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 align-top text-gray-700">{{ $announcement->id }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900">{{ $announcement->title }}</span>
                                            @if(!empty($announcement->summary))
                                                <span class="mt-0.5 line-clamp-1 text-xs text-gray-500">{{ $announcement->summary }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top max-w-[20rem] whitespace-nowrap overflow-hidden text-ellipsis text-gray-700">{{ $announcement->content }}</td>
                                    <td class="px-4 py-3 align-top">
                                        @php
                                            $type = strtolower($announcement->type ?? '');
                                            $typeColor = match(true) {
                                                in_array($type, ['info']) => 'bg-blue-50 text-blue-700 ring-blue-200',
                                                in_array($type, ['warning']) => 'bg-amber-50 text-amber-800 ring-amber-200',
                                                in_array($type, ['event']) => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            };
                                            $typeLabel = match(true) {
                                                in_array($type, ['info'])    => 'お知らせ',
                                                in_array($type, ['warning']) => '注意',
                                                in_array($type, ['event'])   => 'イベント',
                                                default => 'その他',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeColor }}">{{ $typeLabel }}</span>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @php
                                            $status = strtolower($announcement->status ?? '');
                                            $statusColor = match(true) {
                                                in_array($status, ['draft']) => 'bg-gray-100 text-gray-700 ring-gray-300',
                                                in_array($status, ['scheduled','pending']) => 'bg-amber-50 text-amber-800 ring-amber-200',
                                                in_array($status, ['published','active']) => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                in_array($status, ['archived','disabled']) => 'bg-rose-50 text-rose-700 ring-rose-200',
                                                default => 'bg-slate-100 text-slate-700 ring-slate-300',
                                            };
                                            $statusLabel = match(true) {
                                                in_array($status, ['draft'])                 => '下書き',
                                                in_array($status, ['published'])   => '公開中',
                                                in_array($status, ['archived'])  => '非公開',
                                                default                                      => '不明',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColor }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700">
                                        <div class="flex items-center gap-1">
                                            <span>{{ $announcement->start_date ? $announcement->start_date->format('Y-m-d') : '-' }}</span>
                                            <span class="text-gray-400">〜</span>
                                            <span>{{ $announcement->end_date ? $announcement->end_date->format('Y-m-d') : '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700">{{ $announcement->creator->name ?? '-' }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button"
                                                    class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                                    data-modal-target="edit"
                                                    data-id="{{ $announcement->id }}"
                                                    data-title="{{ $announcement->title }}"
                                                    data-content="{{ e($announcement->content) }}"
                                                    data-type="{{ $announcement->type }}"
                                                    data-status="{{ $announcement->status }}"
                                                    data-start_date="{{ $announcement->start_date ? $announcement->start_date->format('Y-m-d') : '' }}"
                                                    data-end_date="{{ $announcement->end_date ? $announcement->end_date->format('Y-m-d') : '' }}"
                                                    data-send_push="{{ (int)($announcement->send_push ?? 0) }}">
                                                編集
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center rounded-md bg-rose-600 px-2.5 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                                                    data-modal-target="delete"
                                                    data-id="{{ $announcement->id }}">
                                                削除
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">お知らせがありません。</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $announcements->withQueryString()->links() }}
                    </div>
                    
                    <!-- Create Modal -->
                    <div id="modal-create" class="hidden fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/30" data-modal-overlay></div>
                        <div class="relative w-full max-w-2xl rounded-lg bg-white shadow-xl ring-1 ring-gray-200">
                            <div class="flex items-center justify-between border-b px-5 py-3">
                                <h3 class="text-base font-semibold text-gray-900">お知らせを作成</h3>
                                <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
                            </div>
                            <form id="form-create" action="{{ route('admin.announcement.store') }}" method="POST" class="px-5 py-4 space-y-4">
                                @csrf
                                <div id="errors-create" class="hidden rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">タイトル</label>
                                    <input type="text" name="title" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">本文</label>
                                    <textarea name="content" rows="5" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">タイプ</label>
                                        <select name="type" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="info">お知らせ</option>
                                            <option value="warning">注意</option>
                                            <option value="event">イベント</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">ステータス</label>
                                        <select name="status" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="下書き">下書き</option>
                                            <option value="published">公開中</option>
                                            <option value="archived">非公開</option>
                                        </select>
                                    </div>
                                    <!-- <div class="flex items-center gap-2 pt-6">
                                        <input id="create-send-push" type="checkbox" name="send_push" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <label for="create-send-push" class="text-sm text-gray-700">プッシュ通知を送る</label>
                                    </div> -->
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">開始日</label>
                                        <input type="date" name="start_date" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">終了日</label>
                                        <input type="date" name="end_date" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-2 border-t pt-4">
                                    <button type="button" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" data-modal-close>キャンセル</button>
                                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">作成</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/30" data-modal-overlay></div>
                        <div class="relative w-full max-w-2xl rounded-lg bg-white shadow-xl ring-1 ring-gray-200">
                            <div class="flex items-center justify-between border-b px-5 py-3">
                                <h3 class="text-base font-semibold text-gray-900">お知らせを編集</h3>
                                <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
                            </div>
                            <form id="form-edit" action="#" method="POST" class="px-5 py-4 space-y-4">
                                @csrf
                                @method('PUT')
                                <div id="errors-edit" class="hidden rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">タイトル</label>
                                    <input type="text" name="title" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">本文</label>
                                    <textarea name="content" rows="5" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">タイプ</label>
                                        <select name="type" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="info">お知らせ</option>
                                            <option value="warning">注意</option>
                                            <option value="event">イベント</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">ステータス</label>
                                        <select name="status" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="draft">下書き</option>
                                            <option value="published">公開中</option>
                                            <option value="archived">非公開</option>
                                        </select>
                                    </div>
                                    <!-- <div class="flex items-center gap-2 pt-6">
                                        <input id="edit-send-push" type="checkbox" name="send_push" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <label for="edit-send-push" class="text-sm text-gray-700">プッシュ通知を送る</label>
                                    </div> -->
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">開始日</label>
                                        <input type="date" name="start_date" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">終了日</label>
                                        <input type="date" name="end_date" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-2 border-t pt-4">
                                    <button type="button" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" data-modal-close>キャンセル</button>
                                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">更新</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Confirm Modal -->
                    <div id="modal-delete" class="hidden fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/30" data-modal-overlay></div>
                        <div class="relative w-full max-w-md rounded-lg bg-white shadow-xl ring-1 ring-gray-200">
                            <div class="px-5 py-5">
                                <h3 class="text-base font-semibold text-gray-900">削除してもよろしいですか？</h3>
                                <p class="mt-2 text-sm text-gray-600">この操作は取り消せません。</p>
                                <div class="mt-5 flex items-center justify-end gap-2">
                                    <button type="button" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" data-modal-close>キャンセル</button>
                                    <form id="form-delete" action="#" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700">削除する</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection

@push('scripts')
    <script>
        (function(){
            const body = document.body;
            function openModal(id){
                const el = document.getElementById(`modal-${id}`);
                if(!el) return;
                el.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            }
            function closeModalAll(){
                document.querySelectorAll('[id^="modal-"]').forEach(m=>m.classList.add('hidden'));
                body.classList.remove('overflow-hidden');
            }

            document.addEventListener('click', function(e){
                const btn = e.target.closest('[data-modal-target]');
                if(!btn) return;
                const target = btn.getAttribute('data-modal-target');
                if(target === 'create'){
                    const form = document.getElementById('form-create');
                    if(form){ form.reset(); const box = document.getElementById('errors-create'); if(box){ box.classList.add('hidden'); box.innerHTML=''; } }
                    openModal('create');
                    return;
                }
                if(target === 'edit'){
                    const form = document.getElementById('form-edit');
                    if(form){
                        const id = btn.getAttribute('data-id');
                        const updateTemplate = @json(route('admin.announcement.update', ['id' => '__ID__']));
                        form.action = updateTemplate.replace('__ID__', id);
                        form.querySelector('[name="title"]').value = btn.getAttribute('data-title') || '';
                        form.querySelector('[name="content"]').value = btn.getAttribute('data-content') || '';
                        form.querySelector('[name="type"]').value = btn.getAttribute('data-type') || '';
                        form.querySelector('[name="status"]').value = btn.getAttribute('data-status') || '';
                        form.querySelector('[name="start_date"]').value = btn.getAttribute('data-start_date') || '';
                        form.querySelector('[name="end_date"]').value = btn.getAttribute('data-end_date') || '';
                        const sendPushEl = form.querySelector('[name="send_push"]');
                        if(sendPushEl){ sendPushEl.checked = btn.getAttribute('data-send_push') === '1'; }
                        const box = document.getElementById('errors-edit'); if(box){ box.classList.add('hidden'); box.innerHTML=''; }
                    }
                    openModal('edit');
                    return;
                }
                if(target === 'delete'){
                    const id = btn.getAttribute('data-id');
                    const form = document.getElementById('form-delete');
                    if(form){
                        const deleteTemplate = @json(route('admin.announcement.destroy', ['id' => '__ID__']));
                        form.action = deleteTemplate.replace('__ID__', id);
                    }
                    openModal('delete');
                }
            });

            document.addEventListener('click', function(e){
                if(e.target.matches('[data-modal-close]')){
                    closeModalAll();
                }
                const overlay = e.target.closest('[data-modal-overlay]');
                if(overlay){ closeModalAll(); }
            });

            document.addEventListener('keydown', function(e){
                if(e.key === 'Escape') closeModalAll();
            });

            async function submitFormAjax(form, errorsBox){
                const submitBtn = form.querySelector('button[type="submit"]');
                const tokenEl = form.querySelector('input[name="_token"]');
                const token = tokenEl ? tokenEl.value : '';
                const formData = new FormData(form);
                if (errorsBox){ errorsBox.classList.add('hidden'); errorsBox.innerHTML=''; }
                if (submitBtn){ submitBtn.disabled = true; submitBtn.classList.add('opacity-60','cursor-not-allowed'); }
                try{
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: formData,
                    });
                    if(res.ok){
                        closeModalAll();
                        window.location.reload();
                        return;
                    }
                    if(res.status === 422){
                        const json = await res.json();
                        const messages = json.errors ? Object.values(json.errors).flat() : [json.message || '入力エラーが発生しました'];
                        if(errorsBox){
                            errorsBox.innerHTML = messages.map(m=>`<div>・${m}</div>`).join('');
                            errorsBox.classList.remove('hidden');
                        }
                        return;
                    }
                    const text = await res.text();
                    if(errorsBox){ errorsBox.textContent = 'エラーが発生しました。'; errorsBox.classList.remove('hidden'); }
                    console.error('Request failed', res.status, text);
                }catch(err){
                    if(errorsBox){ errorsBox.textContent = '通信に失敗しました。'; errorsBox.classList.remove('hidden'); }
                    console.error(err);
                }finally{
                    if (submitBtn){ submitBtn.disabled = false; submitBtn.classList.remove('opacity-60','cursor-not-allowed'); }
                }
            }

            const createForm = document.getElementById('form-create');
            if(createForm){
                createForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    submitFormAjax(createForm, document.getElementById('errors-create'));
                });
            }
            const editForm = document.getElementById('form-edit');
            if(editForm){
                editForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    submitFormAjax(editForm, document.getElementById('errors-edit'));
                });
            }
            const deleteForm = document.getElementById('form-delete');
            if(deleteForm){
                deleteForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    submitFormAjax(deleteForm, null);
                });
            }
        })();
    </script>
@endpush