<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>SOS 緊急通知</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f9fafb; padding:20px;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table width="600" cellpadding="20" cellspacing="0" style="background:#ffffff; border-radius:8px;">

                    <!-- タイトル -->
                    <tr>
                        <td style="text-align:center; background:#dc2626; color:#ffffff; border-radius:6px;">
                            <h1 style="margin:0;">🚨 SOS 緊急通知 🚨</h1>
                        </td>
                    </tr>

                    <!-- 本文 -->
                    <tr>
                        <td>
                            <p>以下のユーザーから <strong>SOS（緊急通知）</strong> が送信されました。</p>

                            <hr>

                            <p><strong>送信者：</strong><br>
                                {{ $user_name ?? '不明' }}
                            </p>

                            <p><strong>送信日時：</strong><br>
                                {{ $sent_at ?? now()->format('Y/m/d H:i') }}
                            </p>

                            <p><strong>メッセージ：</strong></p>
                            <p style="background:#f3f4f6; padding:10px; border-radius:4px;">
                                {{ $messageText ?? 'メッセージはありません' }}
                            </p>

                            @if(!empty($latitude) && !empty($longitude))
                                <p><strong>現在地：</strong></p>
                                <p>
                                    緯度：{{ $latitude }}<br>
                                    経度：{{ $longitude }}
                                </p>

                                <p>
                                    📍
                                    <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}"
                                       target="_blank">
                                        Googleマップで確認する
                                    </a>
                                </p>
                            @endif

                            <hr>

                            <p style="color:#dc2626;">
                                ※このメールは緊急通知です。<br>
                                可能な限り速やかにご確認・ご対応ください。
                            </p>
                        </td>
                    </tr>

                    <!-- フッター -->
                    <tr>
                        <td style="text-align:center; color:#9ca3af; font-size:12px;">
                            <p>MyAI / SOS Notification System</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
