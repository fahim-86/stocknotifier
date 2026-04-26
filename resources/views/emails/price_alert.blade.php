@component('mail::message')
# Price Alert Triggered

**{{ $tradingCode }}** just hit your {{ $triggerType }} target price.

Current LTP: **{{ number_format($ltp, 2) }}**

Thanks,<br>
{{ config('app.name') }}
@endcomponent