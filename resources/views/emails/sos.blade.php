@component('mail::message')
# 🚨 SOS通知

{{ $senderName }} さんから緊急メッセージが届きました。

---

### 📩 メッセージ内容
{{ $messageText }}

---

必要であればご連絡をお願いします。  
@endcomponent
