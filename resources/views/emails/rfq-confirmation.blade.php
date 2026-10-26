<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"><title>تأیید استعلام قیمت</title></head>
<body style="font-family: Tahoma, sans-serif; direction: rtl; background:#f5f5f5; padding:20px;">
  <div style="max-width:600px; margin:0 auto; background:#fff; padding:30px; border-radius:8px;">
    <h2 style="color:#1A237E;">درخواست استعلام شما ثبت شد</h2>
    <p>{{ $rfq->name }} عزیز،</p>
    <p>درخواست استعلام قیمت شما با شماره پیگیری زیر ثبت شد:</p>
    <p style="font-size:20px; font-weight:bold; color:#1A237E; text-align:center; padding:15px; background:#f0f0ff; border-radius:6px;">
      {{ $rfq->reference_number }}
    </p>
    <p>کارشناسان ما ظرف ۲۴ ساعت کاری با شما تماس خواهند گرفت.</p>
    <hr style="margin:20px 0; border:none; border-top:1px solid #eee;">
    <p style="color:#666; font-size:13px;">تول‌مستر — تجهیزات ابزار دقیق صنعتی</p>
  </div>
</body>
</html>
