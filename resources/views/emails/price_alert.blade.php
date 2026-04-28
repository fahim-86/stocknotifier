<x-guest-layout>
    ('mail::message')
    <div>
        # Price Alert Triggered
    </div>

    **{{ $tradingCode }}** just hit your {{ $triggerType }} target price.<br>

    Current LTP: **{{ number_format($ltp, 2) }}**

    Thanks,<br>
    {{ config('app.name') }}

</x-guest-layout>