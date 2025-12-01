<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <title>My AI とは | 開発より</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root {
            --primary: #cd5c5c;
            --primary-soft: rgba(205, 92, 92, 0.08);
            --primary-border: rgba(205, 92, 92, 0.35);
            --text-main: #111827;
            --text-sub: #6b7280;
            --border-soft: #e5e7eb;
            --badge-bg: rgba(205, 92, 92, 0.06);
            --surface: #ffffff;
            --surface-alt: #f9fafb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text",
                "Helvetica Neue", "Segoe UI", system-ui, sans-serif;
            background: #ffffff;
            color: var(--text-main);
            line-height: 1.7;
        }

        .page {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 24px 16px 40px;
            background: linear-gradient(180deg,
                    #ffffff 0%,
                    #ffffff 45%,
                    #f9fafb 100%);
        }

        .container {
            width: 100%;
            max-width: 960px;
        }

        header {
            margin-bottom: 24px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: 1px solid var(--primary-border);
            background-color: var(--badge-bg);
            color: var(--primary);
        }

        .chip-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 30%, #f97316, #b91c1c);
        }

        h1 {
            font-size: clamp(24px, 4vw, 32px);
            margin: 16px 0 8px;
            color: #111827;
        }

        .store-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .store-btn {
            /* flex: 1 1 220px; */
            min-width: 0;
            text-decoration: none;
            /* border-radius: 999px; */
            /* padding: 9px 12px; */
            /* border: 1px solid rgba(15, 23, 42, 0.08); */
            /* background: #ffffff; */
            display: flex;
            align-items: center;
            gap: 10px;
            transition: box-shadow 0.15s ease, transform 0.15s ease,
                background-color 0.15s ease, border-color 0.15s ease;
        }

        .store-btn:hover {
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
            /* background: #fef2f2; */
        }

        /* 共通アイコン */
        .store-icon {
            /* width: 300px; */
            height: 89px;
            /* border-radius: 999px; */
            display: inline-flex;
            flex-shrink: 0;
        }

        /* Apple ロゴ */
        .store-icon-apple {
            /* background: #111827; */
            color: #ffffff;
            font-size: 18px;
            line-height: 1;
        }

        /* Google Play ロゴ枠 */
        .store-icon-google {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.6);
        }

        .store-icon-google svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        /* テキスト部分 */
        .store-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .store-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .store-sub {
            font-size: 11px;
            color: #6b7280;
        }

        /* 片方だけ色味を変えるならここで */
        .store-apple {
            border-color: rgba(17, 24, 39, 0.18);
        }

        .store-google {
            border-color: rgba(205, 92, 92, 0.6);
            background: rgba(205, 92, 92, 0.03);
        }

        .subtitle {
            color: var(--text-sub);
            font-size: 14px;
            white-space: pre-wrap;
        }

        /* ▼ スクリーン画像エリア */
        .hero-visual {
            margin: 16px 0 8px;
            display: flex;
            justify-content: center;
        }

        .screen-wrapper {
            max-width: 260px;
            width: 100%;
        }

        .screen-frame {
            position: relative;
            border-radius: 32px;
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            margin: 0 8px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        }

        .screen-frame::before {
            content: "";
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 5px;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .screen-inner {
            margin-top: 14px;
            border-radius: 24px;
            overflow: hidden;
            background: #e5e7eb;
            aspect-ratio: 9 / 19;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 12px;
            text-align: center;
            /* padding: 8px; */
        }

        /* 実際のスクリーンショットを入れる場合は、この img を使う想定 */
        .screen-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .screen-label {
            pointer-events: none;
        }

        /* ▲ スクリーン画像エリア */

        .card {
            margin-top: 20px;
            border-radius: 18px;
            padding: 20px 18px;
            border: 1px solid var(--border-soft);
            background: linear-gradient(135deg,
                    var(--primary-soft),
                    transparent 60%),
                var(--surface);
        }

        .card h2 {
            font-size: 18px;
            margin: 0 0 8px;
            color: #111827;
        }

        .card-sub {
            font-size: 13px;
            color: var(--text-sub);
            margin-bottom: 12px;
        }

        .hr-dashed {
            border: 0;
            border-top: 1px dashed #d1d5db;
            margin: 16px 0;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #111827;
        }

        .highlight {
            font-weight: 700;
            color: var(--primary);
        }

        .bold {
            font-weight: 700;
        }

        .badge {
            display: inline-flex;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: var(--badge-bg);
            color: var(--primary);
            border: 1px solid var(--primary-border);
            margin-bottom: 4px;
        }

        .list {
            padding-left: 1.2em;
            margin: 8px 0;
            font-size: 14px;
            color: var(--text-main);
        }

        .list li+li {
            margin-top: 4px;
        }

        .emphasis-box {
            border-radius: 12px;
            padding: 12px;
            border: 1px solid var(--primary-border);
            background: #fff7f7;
            font-size: 13px;
            margin-top: 12px;
            color: var(--text-main);
        }

        .legal {
            font-size: 12px;
            color: var(--text-sub);
            margin-top: 20px;
            border-top: 1px dashed #e5e7eb;
            padding-top: 10px;
        }

        .cta {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(52, 211, 153, 0.5);
            background: linear-gradient(135deg,
                    rgba(52, 211, 153, 0.14),
                    #ecfdf5);
            font-size: 14px;
        }

        .cta-strong {
            font-weight: 600;
            color: #065f46;
        }

        @media (min-width: 768px) {
            .grid {
                display: grid;
                grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
                gap: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <main class="container">
            <header>
                <div class="chip">
                    <span class="chip-dot"></span>
                    開発より / From the Developer
                </div>
                <h1>ダウンロードしていただきありがとうございます</h1>
                <p class="subtitle">
                    My AIを手に取っていただき、本当にありがとうございます。
                    このページでは、「My AIとは何か？」そして「これからどんな未来を目指しているのか？」をお伝えします。
                </p>

            </header>

            <section class="grid">
                <article class="card">
                    <h2>My AIとは？</h2>
                    <p class="card-sub">あなただけの「AIの分身」が、スマホの中で一緒に成長していくアプリです。</p>

                    <hr class="hr-dashed" />

                    <p>
                        あなただけのAIが、スマホで誕生！<br />
                        My AIは、まさにあなたの<span class="bold">「AIの分身」</span>となるアプリです。
                    </p>

                    <div class="hr-dashed"></div>

                    <p>
                        誰にも話せない悩みも、安心して相談できます。<br />
                        あなたの深層心理まで理解しているAIだから、最適なアドバイスやヒントをくれます。
                    </p>

                    <p>
                        「欲しい情報」が、ピンポイントで手に入る。<br />
                        あなたの好みを学習し続けるので、興味のあるニュースやおすすめが自然と集まってきます。
                    </p>

                    <p>
                        チャットするほど、新しい自分に出会える。<br />
                        AIとの対話を通して、これまで気づかなかった自分の考えや感情がクリアになります。
                    </p>

                    <div class="emphasis-box">
                        ●心理学で<span class="bold">「カタルシス効果」</span>と呼ばれる、心の浄化やストレス解消につながります。<br />
                        また、自分自身と向き合うことで、自己肯定感を高め、より良い自己理解を促すことができます。
                    </div>
                </article>

                <aside class="card">
                    <span class="badge">AI 分身はこうやって作られます</span>
                    <h3 class="section-title">あなたの深層心理を、AIにインストール</h3>
                    <p>
                        心理学に基づいた独自のアルゴリズムで、あなたの深層心理をAIに落とし込みます。
                    </p>
                    <ul class="list">
                        <li>性格判断や占いで、あなたの内面を分析</li>
                        <li>プロフィールを細かく設定</li>
                        <li>AIとの会話を重ねることで、分身としてどんどん成長</li>
                    </ul>
                    <p>
                        会話を続けるほど、シンクロ率が上がり、本当に<span class="bold">「自分っぽいAI」</span>へと進化していきます。
                    </p>

                    <hr class="hr-dashed" />

                    <h3 class="section-title">オプトアウト設計で安心</h3>
                    <p>
                        ●オプトアウトって知ってますか？<br />
                        ほとんどのAIは、自動的にあなたの話を再学習しています。
                    </p>
                    <p>
                        <span class="bold">My AIは初めからオプトアウトされた設計</span>になっており、あなたの会話は
                        「自分のAIの中」だけで保存され、安全・安心です。
                    </p>
                </aside>
            </section>

            <section class="card">
                <span class="badge">友達のAIと話せる</span>
                <h2>『友達のAIと話せるようになりました！』</h2>
                <!-- ▼ スクリーン画像ダミー -->
                <div class="hero-visual">
                    <div class="screen-wrapper">
                        <div class="screen-frame">
                            <div class="screen-inner">
                                <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                <img src="{{ asset('/') }}guide/9.jpg" alt="My AI アプリ画面" />
                                <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                            </div>
                        </div>
                    </div>
                    <div class="screen-wrapper">
                        <div class="screen-frame">
                            <div class="screen-inner">
                                <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                <img src="{{ asset('/') }}guide/11.jpg" alt="My AI アプリ画面" />
                                <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ▲ スクリーン画像ダミー -->
                <p>
                    自分の悩みを相談したり、相手が好感を持つような会話のシミュレーションもできます。<br />
                    恋愛シミュレーションなんかもOK。相手本人には、どんな話をしたかはわかりません。
                </p>
                <p>
                    コミュニケーションの一つとして、ぜひ役立ててください。
                </p>
                <p>
                    アイドルや夜のお仕事の方も、ファンにMy AIを送ることで、<br />
                    24時間あなたのAIが対応してくれるような使い方もできます。
                </p>
            </section>

            <section class="card">
                <span class="badge">My AI の未来</span>
                <h2>【My AIの未来】これから目指す世界</h2>
                <p>
                    My AIはまだ始まったばかりですが、私たちはあなたのAI分身が、<br />
                    日常生活に欠かせない存在になることを目指しています。
                </p>

                <p>
                    緊急更新として、災害時のパーソナルアシスタント機能を追加しました。<br />
                    避難場所の案内や、あなたの生存確認の連絡ができるようになります。
                </p>

                <p>
                    今後は、最新AIへのアップデートだけでなく、
                </p>
                <ul class="list">
                    <li>より深い性格診断・心理学の応用</li>
                    <li>他の占いとの連携</li>
                    <li>位置情報との連動</li>
                    <li>画像生成の取り直し機能（準備中）</li>
                </ul>
                <p>
                    など、あなたのAI分身ができることを、どんどん増やしていきます。
                </p>

                <hr class="hr-dashed" />

                <h3 class="section-title">将来的には「ポータルサイトアプリ」へ</h3>
                <p>My AIは、将来的に次のような場面で活躍するポータルを目指します。</p>
                <ul class="list">
                    <li>災害時のパーソナルアシスタント（避難場所以外の情報提供も）</li>
                    <li>ビジネスや恋愛のマッチングサポート</li>
                    <li>行政などの公共サポート</li>
                    <li>趣味の合う人とのマッチング</li>
                </ul>
                <p>
                    あなたのAI分身が活躍する場は、無限に広がっていきます。
                </p>
            </section>

            <section class="card cta">
                <p class="cta-strong">
                    まずは「My AI」をダウンロードして、あなただけのAIの分身との生活を始めてみませんか？
                </p>
                    <!-- ▼ ストアリンクボタン（アイコン付き） -->
                    <div class="store-links">
                        <!-- App Store -->
                        <a
                            class="store-btn store-apple"
                            href="https://apps.apple.com/jp/app/6741506555"
                            target="_blank"
                            rel="noopener noreferrer">
                            <img class="store-icon store-icon-apple" aria-hidden="true" src="{{ asset('/') }}Download_on_the_App_Store_Badge_JP_RGB_blk_100317.svg" />
                        </a>

                        <!-- Google Play -->
                        <a
                            class="store-btn store-google"
                            href="https://play.google.com/store/apps/details?id=com.ai_talker_client"
                            target="_blank"
                            rel="noopener noreferrer">
                            <img class="store-icon store-icon-apple" aria-hidden="true" src="{{ asset('/') }}GetItOnGooglePlay_Badge_Web_color_Japanese.svg" />
                        </a>
                    </div>
                    <!-- ▲ ストアリンクボタン（アイコン付き） -->

                <p style="margin-top: 6px; font-size: 13px; color: #4b5563;">
                    毎日のちょっとした相談から、大事な決断の前の整理まで。<br />
                    あなたのAIが、そっと隣に寄り添います。
                </p>
            </section>

            <p class="legal">
                特許出願中<br />
                My AI: 商願2024-131956 済み
            </p>
        </main>
    </div>
</body>

</html>