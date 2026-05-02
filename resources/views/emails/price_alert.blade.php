<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 24px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            padding: 32px;
            max-width: 480px;
            margin: auto;
            border: 1px solid #e0e0e0;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .badge-high {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-low {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-both {
            background: #fef9c3;
            color: #854d0e;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .label {
            color: #666;
            font-size: 13px;
        }

        .value {
            font-weight: bold;
            color: #111;
        }

        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>

<body>
    <div class="card">
        @php
        $badgeClass = match($triggerType) {
        'high' => 'badge-high',
        'low' => 'badge-low',
        default => 'badge-both',
        };
        $label = match($triggerType) {
        'high' => '🔴 HIGH target reached',
        'low' => '🟢 LOW target reached',
        default => '⚡ Both targets reached',
        };
        @endphp

        <span class="badge {{ $badgeClass }}">{{ $label }}</span>

        <h2 style="margin: 0 0 20px; font-size: 22px;">{{ $alert->trading_code }}</h2>

        <div class="row">
            <span class="label">Current LTP </span>
            <span class="value">৳ {{ number_format($ltp, 2) }}</span>
        </div>
        @if($alert->high_price)
        <div class="row">
            <span class="label">Your high target</span>
            <span class="value">৳ {{ number_format($alert->high_price, 2) }}</span>
        </div>
        @endif
        @if($alert->low_price)
        <div class="row">
            <span class="label">Your low target </span>
            <span class="value">৳ {{ number_format($alert->low_price, 2) }}</span>
        </div>
        @endif
        <div class="row">
            <span class="label">Alert triggered at </span>
            <span class="value">{{ now('Asia/Dhaka')->format('d M Y, h:i A') }} BST</span>
        </div>

        <p class="footer">This alert has been deactivated. <a class="btn" href="{{ url('/dashboard') }}">Log back in</a> to set a new one.</p>

    </div>
</body>

</html>