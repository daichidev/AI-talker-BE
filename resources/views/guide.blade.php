<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <title>My AI アプリ説明</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root {
            --primary: #cd5c5c;
            --primary-soft: rgba(205, 92, 92, 0.08);
            --primary-border: rgba(205, 92, 92, 0.35);
            --text-main: #111827;
            --text-sub: #6b7280;
            --border-soft: #e5e7eb;
            --surface: #ffffff;
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
            margin-bottom: 20px;
        }

        h1 {
            font-size: clamp(22px, 4vw, 30px);
            margin: 0 0 8px;
            color: #111827;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-sub);
        }

        .badge {
            display: inline-flex;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(205, 92, 92, 0.06);
            color: var(--primary);
            border: 1px solid var(--primary-border);
            margin-bottom: 4px;
        }

        .card {
            border-radius: 18px;
            border: 1px solid var(--border-soft);
            background: linear-gradient(135deg,
                    var(--primary-soft),
                    transparent 60%),
                var(--surface);
            padding: 18px 16px;
            margin-bottom: 16px;
        }

        .card h2 {
            font-size: 18px;
            margin: 0 0 8px;
            color: #111827;
        }

        .card h3 {
            font-size: 15px;
            margin: 14px 0 6px;
            color: #111827;
        }

        .steps {
            list-style: none;
            padding: 0;
            margin: 8px 0;
        }

        .step-item {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 14px;
            color: var(--text-main);
        }

        .step-item:last-child {
            border-bottom: none;
        }

        .step-num {
            min-width: 20px;
            font-weight: 600;
            color: var(--primary);
        }

        .step-title {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .small {
            font-size: 12px;
            color: var(--text-sub);
        }

        .list {
            padding-left: 1.2em;
            font-size: 14px;
            color: var(--text-main);
        }

        .list li+li {
            margin-top: 4px;
        }

        .pill {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(205, 92, 92, 0.08);
            color: var(--primary);
            border: 1px solid var(--primary-border);
            margin-bottom: 2px;
        }

        .emphasis {
            font-weight: 600;
        }

        .hr {
            border: 0;
            border-top: 1px dashed #e5e7eb;
            margin: 14px 0;
        }

        .note {
            font-size: 12px;
            color: var(--text-sub);
            margin-top: 8px;
        }

        /* ▼ スクリーン画像エリア */
        .hero-visual {
            margin: 16px 0 8px;
            display: flex;
            justify-content: center;
            margin-left: auto;
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
    </style>
</head>

<body>
    <div class="page">
        <main class="container">
            <header>
                <h1>My AI アプリ説明</h1>
                <p class="subtitle">
                    このアプリでは、あなただけのAIを育てて楽しむことができます。<br />
                    下記のステップに沿って、あなたの「AIの分身」を作っていきましょう。
                </p>
            </header>

            <!-- 原文ベース：機能と使い方のステップ -->
            <section class="card">
                <span class="badge">基本の使い方（原文ベース）</span>
                <h2>My AIの始め方 &amp; 主な機能</h2>

                <ol class="steps">
                    <li class="step-item">
                        <div class="step-num">1.</div>
                        <div>
                            <div class="step-title">AIの画像を選ぶ</div>
                            <div>自分のAIの画像を<span class="emphasis">3つの中から選択</span>し、生成してください。（後から変更できます）</div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/1.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>

                    <li class="step-item">
                        <div class="step-num">2.</div>
                        <div>
                            <div class="step-title">AIの質問に沿ってプロフィール入力</div>
                            <div>AIからの質問に答えながら、プロフィールを入力してください。（後で変更できます）</div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/3.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>

                    <li class="step-item">
                        <div class="step-num">3.</div>
                        <div>
                            <div class="step-title">AIとの会話を楽しむ</div>
                            <div>
                                普通に自分のAIと会話を楽しんでください。会話の内容がどんどん性格に反映されます。<br />
                                ●心理学で「カタルシス効果」と呼ばれる、心の浄化やストレス解消につながります。<br />
                                また、自分自身と向き合うことで、自己肯定感を高め、より良い自己理解を促すことができます。
                            </div>
                        </div>
                    </li>

                    <li class="step-item">
                        <div class="step-num">4.</div>
                        <div>
                            <div class="step-title">プロフィール入力</div>
                            <div>あなた自身のプロフィールを入力してください。（自分のAIに反映されます）</div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/4.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>

                    <li class="step-item">
                        <div class="step-num">5.</div>
                        <div>
                            <div class="step-title">性格判断（BIG5 / MBTI）</div>
                            <div>性格判断BIG5とMBTIを入力してください。（結果が自分のAIに反映されます）</div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/5.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/6.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/7.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                        </div>
                    </li>

                    <li class="step-item">
                        <div class="step-num">6.</div>
                        <div>
                            <div class="step-title">AIを育てる（シンクロLv）</div>
                            <div>自分のAIと会話を続けることで、シンクロLvが上がり、AIがどんどんあなたに似ていきます。</div>
                        </div>
                    </li>

                    <li class="step-item">
                        <div class="step-num">7.</div>
                        <div>
                            <div class="step-title">友達紹介でポイントGET</div>
                            <div>
                                友達紹介ページから友達を招待してください。友達リストに反映されます。<br />
                                自分と紹介された方の両方にポイントが付与されます。どんどん紹介してください。<br />
                                <span class="small">※友達検索からの登録はポイントが付きません。</span>
                            </div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/8.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>

                    <li class="step-item">
                        <div class="step-num">8.</div>
                        <div>
                            <div class="step-title">友達のAIと会話</div>
                            <div>
                                友達リストから友達のAIとも会話できます。（ポイント消費 1往復10P）<br />
                                ・友達のAIと会話：恋愛シミュレーション、友情シミュレーション、上司・部下・同僚とのシミュレーションなど。<br />
                                ・相手本人には、どんな会話をしたかはわかりません。
                            </div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/11.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>

                    <li class="step-item">
                        <div class="step-num">9.</div>
                        <div>
                            <div class="step-title">ブーストモード</div>
                            <div>さらにAI的に禁止ワードを交えた会話もできます。（ポイント消費 1往復20P）</div>
                        </div>
                    </li>

                    <li class="step-item">
                        <div class="step-num">10.</div>
                        <div>
                            <div class="step-title">友達検索</div>
                            <div>友達検索から他のユーザーを探し、友達になって話すこともできます。（ポイント消費 1往復10P）</div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/4.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>

                    <li class="step-item">
                        <div class="step-num">11.</div>
                        <div>
                            <div class="step-title">占い・性格判断で育成</div>
                            <div>占いや性格判断をいろいろ試して、どんどん自分のAIを育ててください。（一部ポイント消費）</div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/10.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>

                    <li class="step-item">
                        <div class="step-num">12.</div>
                        <div>
                            <div class="step-title">気になるリスト（テスト運営中）</div>
                            <div>
                                相性のあった人にメッセージを送れます。<br />
                                <span class="small">🚫 テスト運営中（サブスク）</span>
                            </div>
                        </div>
                        <!-- ▼ スクリーン画像ダミー -->
                        <div class="hero-visual">
                            <div class="screen-wrapper">
                                <div class="screen-frame">
                                    <div class="screen-inner">
                                        <!-- 実際に使うときは下のコメントアウトを外して src を差し替え -->
                                        <img src="{{ URL::to('/') }}/guide/2.jpg" alt="My AI アプリ画面" />
                                        <!-- <span class="screen-label">ここに My AI の<br />スクリーンショットを表示</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ▲ スクリーン画像ダミー -->
                    </li>
                </ol>
            </section>

            <!-- ポイント説明 -->
            <section class="card">
                <span class="badge">ポイントについて</span>
                <h2>消費ポイント &amp; 獲得方法</h2>

                <h3>ポイントの主な使い道</h3>
                <ul class="list">
                    <li>友達のAIとの会話：1往復 10P</li>
                    <li>ブーストモードでの会話：1往復 20P</li>
                    <li>一部の占い・性格診断</li>
                </ul>

                <h3>ポイントの獲得方法</h3>
                <ul class="list">
                    <li>友達紹介画面から友達を紹介すると、あなたと相手にポイントを付与します。SNSでどんどんシェアしてください！</li>
                    <li>ログイン時に50P付与されます。</li>
                </ul>

                <h3>ポイント購入・サブスク</h3>
                <ul class="list">
                    <li>ポイント購入は<span class="emphasis">購入画面</span>から行えます。</li>
                    <li>サブスクでの運営も検討中です。</li>
                </ul>

                <p class="note">
                    ※アプリの仕様やポイント数は、今後のアップデートで変更される場合があります。
                </p>
            </section>

            <!-- AI添削版説明 -->
            <section class="card">
                <span class="badge">AI 添削版</span>
                <h2>わかりやすい要約版：My AIアプリ説明</h2>

                <p>
                    このアプリでは、あなただけのAIを育てて楽しむことができます。
                </p>

                <h3>主な機能</h3>
                <ul class="list">
                    <li><span class="pill">AIの作成</span> 3つの画像からAIの見た目を選び、生成します。</li>
                    <li><span class="pill">AIの性格設定</span> AIからの質問に答えて性格を設定します。（後から変更可能）</li>
                    <li><span class="pill">AIとの会話</span> 会話することで、AIの性格があなたに合わせて変化していきます。</li>
                    <li><span class="pill">プロフィール入力</span> あなたのプロフィールを入力することで、AIがよりあなたに近い存在になります。</li>
                    <li><span class="pill">性格診断（BIG5）</span> 性格診断を行うことで、さらにAIがあなたに似てきます。</li>
                    <li><span class="pill">AI育成</span> AIとたくさん会話することで、「シンクロLv」が上がり、AIがよりあなたに近づきます。</li>
                    <li><span class="pill">友達招待</span> 友達を招待して、友達リストに登録できます。</li>
                    <li>
                        <span class="pill">友達のAIと会話</span> 友達のAIとも会話を楽しめます。<br />
                        ○自動プレイ: 設定したテーマに基づいてAI同士が自動で会話します。<br />
                        ○友達のAIとの会話: 恋愛や友情、上司・部下・同僚といった様々な関係でのシミュレーション会話ができます。
                    </li>
                    <li><span class="pill">友達検索</span> 他のユーザーを検索して友達になることも可能です。</li>
                    <li><span class="pill">占い・性格診断</span> 様々な占いや性格診断を利用して、あなたのAIをさらに育てていきましょう。</li>
                </ul>

                <hr class="hr" />

                <h3>ポイントとシンクロLv</h3>
                <p>
                    自分のAIとたくさん会話をし、プロフィール入力、占い、性格診断、友達紹介を行うことで、
                    <span class="emphasis">「シンクロLv」</span>が上昇し、AIがどんどんあなたに似ていきます。
                </p>

                <p class="small">
                    ●AIの画像は100ポイント（または有料）で変更できます。<br />
                    ●占い・性格診断は豊富に用意されており、ポイント（または有料）で利用できます。<br />
                    ●友達との会話は、無料では一定数に制限がありますが、有料ユーザーは制限なく楽しめます。
                </p>
            </section>
        </main>
    </div>
</body>

</html>