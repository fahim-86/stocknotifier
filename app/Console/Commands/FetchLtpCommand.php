<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\DseScraperService;
use App\Models\Stock;
use App\Models\Alert;
use App\Mail\PriceAlertReached;

class FetchLtpCommand extends Command
{
    protected $signature = 'fetch:ltp {--force : Skip trading hours check}';
    protected $description = 'Fetch LTP from DSE and fire price alerts';

    public function handle(DseScraperService $scraper)
    {
        $now = Carbon::now('Asia/Dhaka');

        // Trading hours guard: Sunday(0)–Thursday(4), 10:00–14:30
        if (!$this->option('force')) {
            $dayOfWeek = $now->dayOfWeek; // 0=Sun,1=Mon,...,6=Sat
            $isTradeDay = in_array($dayOfWeek, [0, 1, 2, 3, 4]);
            $isTradeTime = $now->between(
                $now->copy()->setTime(10, 0, 0),
                $now->copy()->setTime(14, 30, 0)
            );

            if (!$isTradeDay || !$isTradeTime) {
                $this->info("Outside trading hours ({$now->format('D H:i')}). Skipping.");
                return 0;
            }
        }

        $this->info("Fetching LTP at {$now->format('Y-m-d H:i:s')}...");

        // 2. Scrape latest prices
        try {
            $prices = $scraper->fetchLatestPrices(); // returns Collection of ['trading_code'=>..., 'ltp'=>...]
        } catch (\Throwable $e) {
            Log::error('DSE scraper failed: ' . $e->getMessage());
            $this->error('Scraper error: ' . $e->getMessage());
            return 1;
        }

        if ($prices->isEmpty()) {
            $this->warn('Scraper returned empty data. DSE page may have changed.');
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


    // For testing, we inject the test class that uses a local HTML stub instead of real HTTP calls.
    // public function handle(DsePriceFetcherTest $scraper)
    // {
    //     // 2. Scrape latest prices
    //     $this->info('Fetching latest prices from DSE...');
    //     $prices = $scraper->it_can_fetch_latest_prices_from_local_stub();

    //     if ($prices->isEmpty()) {
    //         $this->warn('No price data returned. Possible network/parsing issue.');
    //         return 1;
    //     }

    //     // 3. Update stocks table
    //     foreach ($prices as $item) {
    //         Stock::updateOrCreate(
    //             ['trading_code' => $item->trading_code],
    //             [
    //                 'ltp' => $item->ltp,
    //                 'last_fetched_at' => now()
    //             ]
    //         );
    //     }
    //     $this->info('Stock prices updated.');

    //     // 4. Check alerts against the new LTPs
    //     $alertsFired = 0;

    //     foreach ($prices as $item) {
    //         $ltp = round((float) $item->ltp, 2);   // ensure numeric float
    //         $code = $item->trading_code;

    //         $matchingAlerts = Alert::where('trading_code', $code)
    //             ->where('is_active', true)
    //             ->priceAlert($ltp)
    //             ->with('user')
    //             ->get();
    //         // $matchingAlerts = Alert::where('trading_code', $code)
    //         //     ->where('is_active', true)
    //         //     ->where(function ($query) use ($ltp) {
    //         //         $query->where(function ($q) use ($ltp) {
    //         //             $q->whereNotNull('high_price')
    //         //                 ->where('high_price', '<=', $ltp);
    //         //         })->orWhere(function ($q) use ($ltp) {
    //         //             $q->whereNotNull('low_price')
    //         //                 ->where('low_price', '>=', $ltp);
    //         //         });
    //         //     })
    //         //     ->with('user')
    //         //     ->get();

    //         foreach ($matchingAlerts as $alert) {
    //             // Cast alert prices to float for safe comparison
    //             $high = is_null($alert->high_price) ? null : round((float) $alert->high_price, 2);
    //             $low  = is_null($alert->low_price)  ? null : (float) $alert->low_price;

    //             // Determine which price matched (priority: high > low)
    //             $triggerType = '';
    //             if (!is_null($high) && $ltp >= $high) {
    //                 $triggerType = 'high';
    //             } elseif (!is_null($low) && $ltp <= $low) {
    //                 $triggerType = 'low';
    //             }

    //             // Send email (queued)
    //             Mail::to($alert->user->email)
    //                 ->queue(new PriceAlertReached($code, $triggerType, $ltp));

    //             // Deactivate alert to avoid repeat mails
    //             $alert->update(['is_active' => false]);

    //             $alertsFired++;

    //             $this->line("Alert sent to {$alert->user->email} for {$code} ({$triggerType} at $ltp).");
    //         }
    //     }

    //     $this->info("Done. {$alertsFired} alert(s) fired.");
    //     return 0;
    // }
}
