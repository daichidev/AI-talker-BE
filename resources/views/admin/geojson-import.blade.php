@extends('layouts.admin')

@section('title', '避難所・指定避難所データ インポート')

@section('content')
{{-- resources/views/geojson-import.blade.php --}}
                {{-- 概要カード --}}
                <section class="bg-white shadow-sm rounded-2xl border border-slate-100 p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                全資料を資料基地に取り込む
                            </h2>
                            <p class="mt-1 text-xs sm:text-sm text-slate-500 leading-relaxed">
                                国土地理院の「指定緊急避難場所・指定避難所データ」を、
                                あらかじめ用意した GeoJSON ファイルリストからすべてダウンロードし、
                                資料基地（MySQL データベース）に登録します。
                            </p>
                        </div>

                        {{-- ステータス簡易表示（任意） --}}
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-slate-50 rounded-xl px-3 py-2 border border-slate-100">
                                <p class="text-[11px] text-slate-500">最終インポート件数</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ $totalInserted ?? 0 }} 件
                                </p>
                            </div>
                            <div class="bg-slate-50 rounded-xl px-3 py-2 border border-slate-100">
                                <p class="text-[11px] text-slate-500">状態</p>
                                @if(isset($totalInserted))
                                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        実行完了
                                    </p>
                                @else
                                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-slate-500">
                                        <span class="inline-block w-2 h-2 rounded-full bg-slate-300"></span>
                                        待機中
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 実行注意メッセージ --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 flex gap-2 items-start">
                        <div class="mt-0.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                                !
                            </span>
                        </div>
                        <p class="text-xs sm:text-[13px] text-amber-800 leading-relaxed">
                            この操作は、全ての GeoJSON ファイルを順番にダウンロードして登録します。
                            初回は時間がかかる場合があります。<br class="hidden sm:block">
                            実行中は、このページを閉じないでください。
                        </p>
                    </div>

                    {{-- 実行ボタン --}}
                    <form method="POST" action="{{ route('admin.geojson.import.run') }}" class="pt-2">
                        @csrf
                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm hover:shadow-md transition-all disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                {{-- アイコン的な丸 --}}
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-200"></span>
                                <span>全ての資料を資料基地に取り込む</span>
                            </button>

                            <!-- <p class="text-[11px] sm:text-xs text-slate-500">
                                ※ 同じボタンを再実行すると、実装に応じて「追加入力」や「重複データ」が発生する可能性があります。
                            </p> -->
                        </div>
                    </form>
                </section>

                {{-- ログ表示エリア --}}
                <section class="bg-white shadow-sm rounded-2xl border border-slate-100">
                    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">
                            実行ログ
                        </h2>
                        <span class="text-[11px] text-slate-400">
                            最新の実行結果のみ表示されます
                        </span>
                    </div>

                    @if(isset($log) && is_array($log) && count($log))
                        <div class="p-4">
                            <div class="bg-slate-950 text-slate-100 rounded-xl text-[11px] sm:text-xs font-mono leading-relaxed p-3 sm:p-4 max-h-80 overflow-auto border border-slate-800">
@foreach($log as $line)
{{ $line }}
@endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-5 text-center text-xs sm:text-sm text-slate-500">
                            まだインポートは実行されていません。
                            <br class="hidden sm:block">
                            上の「全ての資料を資料基地に取り込む」ボタンをクリックすると、ここに進捗ログが表示されます。
                        </div>
                    @endif
                </section>

        {{-- フッター --}}
        <footer class="border-t border-slate-200 bg-white/80 backdrop-blur">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                <span>データ元：指定緊急避難場所・指定避難所データ（都道府県・市町村別）</span>
            </div>
        </footer>
@endsection