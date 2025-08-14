<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アカウント削除リクエスト</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #e74c3c;
            margin: 0;
            font-size: 28px;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
            color: #856404;
        }
        .warning-box h3 {
            margin: 0 0 10px 0;
            color: #e74c3c;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .checkbox-group {
            margin: 20px 0;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .checkbox-item input[type="checkbox"] {
            margin-right: 15px;
            transform: scale(1.2);
        }
        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            margin-right: 15px;
        }
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        .button-group {
            text-align: center;
            margin-top: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ アカウント削除リクエスト</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="warning-box">
            <h3>⚠️ 重要な注意事項</h3>
            <p>アカウントを削除すると、以下のデータが<strong>完全に削除</strong>され、復元することはできません：</p>
            <ul>
                <li>プロフィール情報</li>
                <li>アバター画像</li>
                <li>チャット履歴</li>
                <li>マッチング情報</li>
                <li>その他すべての関連データ</li>
            </ul>
        </div>

        <form action="{{ route('account.post-delete-account') }}" method="POST" id="deleteAccountForm">
            @csrf
            
            <div class="form-group">
                <label for="delete_reason">削除理由を選択してください：</label>
                <select name="delete_reason" id="delete_reason" required>
                    <option value="">理由を選択してください</option>
                    <option value="privacy_concerns">プライバシーの懸念</option>
                    <option value="no_longer_using">もう使用していない</option>
                    <option value="found_better_service">より良いサービスを見つけた</option>
                    <option value="technical_issues">技術的な問題</option>
                    <option value="other">その他</option>
                </select>
            </div>

            <div class="form-group">
                <label for="delete_reason_detail">詳細な理由（任意）：</label>
                <textarea name="delete_reason_detail" id="delete_reason_detail" 
                          placeholder="削除理由について詳しく教えてください。サービス改善の参考にさせていただきます。"></textarea>
            </div>

            <div class="form-group">
                <label for="email">アカウントのメールアドレス：</label>
                <input type="email" name="email" id="email" required 
                       placeholder="削除するアカウントのメールアドレスを入力してください"
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; box-sizing: border-box;">
            </div>

            <div class="form-group">
                <label for="password">パスワード：</label>
                <input type="password" name="password" id="password" required 
                       placeholder="アカウントのパスワードを入力してください"
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; box-sizing: border-box;">
            </div>

            <div class="checkbox-group">
                <h3>削除するデータの確認</h3>
                
                <div class="checkbox-item">
                    <input type="checkbox" id="confirm_profile" name="confirm_profile" required>
                    <label for="confirm_profile">プロフィール情報を削除することを確認します</label>
                </div>
                
                <div class="checkbox-item">
                    <input type="checkbox" id="confirm_avatar" name="confirm_avatar" required>
                    <label for="confirm_avatar">アバター画像を削除することを確認します</label>
                </div>
                
                <div class="checkbox-item">
                    <input type="checkbox" id="confirm_chat" name="confirm_chat" required>
                    <label for="confirm_chat">チャット履歴を削除することを確認します</label>
                </div>
                
                <div class="checkbox-item">
                    <input type="checkbox" id="confirm_matching" name="confirm_matching" required>
                    <label for="confirm_matching">マッチング情報を削除することを確認します</label>
                </div>
                
                <div class="checkbox-item">
                    <input type="checkbox" id="confirm_irreversible" name="confirm_irreversible" required>
                    <label for="confirm_irreversible">削除後は復元できないことを理解しています</label>
                </div>
            </div>

            <div class="button-group">
                <a href="{{ url('/') }}" class="btn btn-secondary">キャンセル</a>
                <button type="submit" class="btn btn-danger" onclick="return confirmDelete()">
                    アカウント削除をリクエスト
                </button>
            </div>
        </form>
    </div>

    <script>
        function confirmDelete() {
            const requiredCheckboxes = document.querySelectorAll('input[type="checkbox"][required]');
            let allChecked = true;
            
            requiredCheckboxes.forEach(checkbox => {
                if (!checkbox.checked) {
                    allChecked = false;
                }
            });
            
            if (!allChecked) {
                alert('すべての確認項目にチェックを入れてください。');
                return false;
            }
            
            return confirm('本当にアカウント削除をリクエストしますか？\nこの操作は取り消すことができません。');
        }

        // フォーム送信前の最終確認
        document.getElementById('deleteAccountForm').addEventListener('submit', function(e) {
            const deleteReason = document.getElementById('delete_reason').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!deleteReason) {
                e.preventDefault();
                alert('削除理由を選択してください。');
                return false;
            }
            
            if (!email) {
                e.preventDefault();
                alert('メールアドレスを入力してください。');
                return false;
            }
            
            if (!password) {
                e.preventDefault();
                alert('パスワードを入力してください。');
                return false;
            }
        });
    </script>
</body>
</html>
