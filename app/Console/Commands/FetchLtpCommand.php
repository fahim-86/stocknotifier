<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Stock;
use App\Services\DseScraperService;
use App\Mail\PriceAlertReached;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class FetchLtpCommand extends Command
{
    protected $signature = 'fetch:ltp';
    protected $description = 'Fetch DSE latest prices, update stocks, and fire alerts';

    public function handle(DseScraperService $scraper)
    {
        // 1. Trading schedule check (Sunday=0 through Thursday=4)
        $now = now()->shiftTimezone('Asia/Dhaka');
        // dump("Current time in Dhaka: {$now->toDateTimeString()}");
        $dayOfWeek = $now->dayOfWeek; // Carbon: 0=Sunday, 4=Thursday

        if ($dayOfWeek < 0 || $dayOfWeek > 4) {
            $this->info('Outside trading days (Sunday–Thursday). Skipping.');
            return 0;
        }

        $timeOfDay = $now->format('H:i');
        if ($timeOfDay < '10:00' || $timeOfDay > '14:30') {
            $this->info('Outside trading hours (10:00–14:30). Skipping.');
            return 0;
        }

        // 2. Scrape latest prices
        $this->info('Fetching latest prices from DSE...');
        $prices = $scraper->fetchLatestPrices();

        if ($prices->isEmpty()) {
            $this->warn('No price data returned. Possible network/parsing issue.');
            return 1;
        }

        // 3. Update stocks table
        foreach ($prices as $item) {
            Stock::updateOrCreate(
                ['trading_code' => $item->trading_code],
                [
                    'ltp' => $item->ltp,
                    'last_fetched_at' => now()
                ]
            );
        }
        $this->info('Stock prices updated.');

        // 4. Check alerts against the new LTPs
        $alertsFired = 0;

        foreach ($prices as $item) {
            $ltp = (float) $item->ltp;   // ensure numeric float
            $code = $item->trading_code;

            $matchingAlerts = Alert::where('trading_code', $code)
                ->where('is_active', true)
                ->where(function ($query) use ($ltp) {
                    $query->where(function ($q) use ($ltp) {
                        $q->whereNotNull('high_price')
                            ->where('high_price', '<=', $ltp);
                    })->orWhere(function ($q) use ($ltp) {
                        $q->whereNotNull('low_price')
                            ->where('low_price', '>=', $ltp);
                    });
                })
                ->with('user')
                ->get();

            foreach ($matchingAlerts as $alert) {
                // Cast alert prices to float for safe comparison
                $high = is_null($alert->high_price) ? null : (float) $alert->high_price;
                $low  = is_null($alert->low_price)  ? null : (float) $alert->low_price;

                // Determine which price matched (priority: high > low)
                $triggerType = '';
                if (!is_null($high) && $ltp >= $high) {
                    $triggerType = 'high';
                } elseif (!is_null($low) && $ltp <= $low) {
                    $triggerType = 'low';
                }

                // Send email (queued)
                Mail::to($alert->user->email)
                    ->queue(new PriceAlertReached($code, $triggerType, $ltp));

                // Deactivate alert to avoid repeat mails
                $alert->update(['is_active' => false]);

                $alertsFired++;

                $this->line("Alert sent to {$alert->user->email} for {$code} ({$triggerType} at $ltp).");
            }
        }

        $this->info("Done. {$alertsFired} alert(s) fired.");
        return 0;
    }
}
