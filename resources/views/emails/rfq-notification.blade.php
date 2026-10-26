<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"><title>استعلام جدید</title></head>
<body style="font-family: Tahoma, sans-serif; direction: rtl; background:#f5f5f5; padding:20px;">
  <div style="max-width:600px; margin:0 auto; background:#fff; padding:30px; border-radius:8px;">
    <h2 style="color:#c0392b;">استعلام جدید: {{ $rfq->reference_number }}</h2>
    <table style="width:100%; border-collapse:collapse;">
      <tr><td><b>نام:</b></td><td>{{ $rfq->name }}</td></tr>
      <tr><td><b>ایمیل:</b></td><td>{{ $rfq->email }}</td></tr>
      <tr><td><b>تلفن:</b></td><td>{{ $rfq->phone }}</td></tr>
      <tr><td><b>شرکت:</b></td><td>{{ $rfq->company }}</td></tr>
    </table>
    <h3>اقلام:</h3>
    <table style="width:100%; border-collapse:collapse; border:1px solid #ddd;">
      <thead><tr style="background:#f0f0f0;"><th style="padding:8px; border:1px solid #ddd;">محصول</th><th style="padding:8px; border:1px solid #ddd;">مدل</th><th style="padding:8px; border:1px solid #ddd;">تعداد</th></tr></thead>
      <tbody>
      @foreach($rfq->items as $item)
        <tr><td style="padding:8px; border:1px solid #ddd;">{{ $item->product_name }}</td><td style="padding:8px; border:1px solid #ddd;">{{ $item->product_model }}</td><td style="padding:8px; border:1px solid #ddd;">{{ $item->quantity }}</td></tr>
      @endforeach
      </tbody>
    </table>
    @if($rfq->notes)<p><b>یادداشت:</b> {{ $rfq->notes }}</p>@endif
    <p><a href="{{ url('/admin/rfqs/' . $rfq->id) }}" style="background:#1A237E;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;">مشاهده در پنل</a></p>
  </div>
</body>
</html>
