<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSE Price Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 560px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
        }

        .header {
            background: {
                    {
                    $triggerType ==='high' ? '#dc2626': '#16a34a'
                }
            }

            ;
            color: #fff;
            padding: 24px 32px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .header p {
            margin: 6px 0 0;
            opacity: .85;
            font-size: 13px;
        }

        .body {
            padding: 28px 32px;
        }

        .stat {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .stat:last-child {
            border-bottom: none;
        }

        .label {
            color: #6b7280;
            font-size: 14px;
        }

        .value {
            font-weight: 700;
            font-size: 15px;
            color: #111;
        }

        .ltp {
            font-size: 26px;

            color: {
                    {
                    $triggerType ==='high' ? '#dc2626': '#16a34a'
                }
            }

            ;
        }

        .footer {
            background: #f9fafb;
            padding: 16px 32px;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 24px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ $triggerType === 'high' ? '🔴 High Price Alert' : '🟢 Low Price Alert' }} from StockBuzz</h1>
            <p>{{ now()->setTimezone('Asia/Dhaka')->format('D, d M Y, h:i A') }} (Dhaka)</p>
        </div>
        <div class="body">
            <div class="stat">
                <span class="label">Trading Code</span>
                <span class="value"><b>{{ $alert->trading_code }}</b></span>
            </div>
            <div class="stat">
                <span class="label">Last Traded Price (LTP)</span>
                <span class="value ltp"><b>৳ {{ number_format($ltp, 2) }}</b></span>
            </div>
            @if($triggerType === 'high')
            <div class="stat">
                <span class="label">Your High Alert</span>
                <span class="value"><b>৳ {{ number_format($alert->high_price, 2) }}</b></span>
            </div>
            @else
            <div class="stat">
                <span class="label">Your Low Alert</span>
                <span class="value">৳ {{ number_format($alert->low_price, 2) }}</span>
            </div>
            @endif
            <div class="stat">
                <span class="label">Alert Status</span>
                <span class="value" style="color:#6b7280">Deactivated after trigger</span>
            </div>

            <a class="btn" href="{{ url('/dashboard') }}">Manage My Alerts</a>
        </div>
        <div class="footer">
            You received this because you set a price alert on DSE Tracker.<br>
            © {{ date('Y') }} DSE Price Alert. Trading hours: Sun–Thu, 10:00–14:30 BDT.
        </div>
    </div>
</body>

</html>