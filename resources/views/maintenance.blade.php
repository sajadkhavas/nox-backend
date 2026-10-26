<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f2027 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
        }
        .icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: spin 3s linear infinite;
        }
        @keyframes spin {
            0%  { transform: rotate(0deg); }
            100%{ transform: rotate(360deg); }
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        p {
            font-size: 1.1rem;
            opacity: 0.85;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        .timer {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1rem 2rem;
            display: inline-block;
            font-size: 0.95rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⚙️</div>
        <h1>{{ $title }}</h1>
        @if($message)
            <p>{{ $message }}</p>
        @endif
        @if($scheduledEnd)
            <div class="timer">
                🕐 بازگشت تخمینی: {{ \Carbon\Carbon::parse($scheduledEnd)->format('Y/m/d H:i') }}
            </div>
        @endif
    </div>
</body>
</html>
