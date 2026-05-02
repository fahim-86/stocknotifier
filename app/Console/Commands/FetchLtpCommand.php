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

    public function handle(DseScraperService $scraper): int
    {

        $now = Carbon::now('Asia/Dhaka');

        if (!$this->option('force')) {
            $this->isTradingTime();

            if (! $this->isTradingTime()) {
                $this->info("Outside trading hours ({$now->format('D H:i')}). Skipping.");
                return self::SUCCESS;
            }
        }

        $prices = $scraper->fetchLatestPrices();

        if ($prices->isEmpty()) {
            Log::warning('fetch:ltp — scraper returned no data');
            $this->warn('No price data received.');
            return self::FAILURE;
        }

        $this->info("Fetched {$prices->count()} prices. Processing alerts...");

        foreach ($prices as $entry) {
            // 1. Upsert stock record
            Stock::updateOrCreate(
                ['trading_code' => $entry->trading_code],
                ['ltp' => $entry->ltp, 'fetched_at' => $now]
            );

            // 2. Find all ACTIVE alerts for this code that are triggered
            //    BUG FIX: eager-load 'user' so we can access user email reliably.
            //    BUG FIX: use ->with('user') to prevent N+1 and null user issues.
            $triggered = Alert::with('user')
                ->where('trading_code', $entry->trading_code)
                ->where('is_active', true)
                ->triggeredBy($entry->ltp)
                ->get();

            foreach ($triggered as $alert) {
                // BUG FIX: guard against orphaned alerts (user deleted)
                if (! $alert->user) {
                    $alert->update(['is_active' => false]);
                    continue;
                }

                $triggerType = $this->resolveTriggerType($alert, $entry->ltp);

                try {
                    // BUG FIX: Pass email STRING explicitly to Mail::to().
                    // Never pass the $alert->user Eloquent model directly —
                    // Laravel queues the first recipient it sees and can skip
                    // subsequent ones when the model is reused across loop iterations.
                    Mail::to($alert->user->email)
                        ->send(new PriceAlertReached($alert, $entry->ltp, $triggerType));

                    // Deactivate after successful dispatch
                    $alert->update(['is_active' => false]);

                    Log::info("Alert fired: {$alert->trading_code} ({$triggerType}) "
                        . "→ {$alert->user->email} @ LTP {$entry->ltp}");

                    $this->line("  ✓ {$alert->trading_code} [{$triggerType}] → {$alert->user->email}");
                } catch (\Throwable $e) {
                    Log::error("Mail send failed for alert #{$alert->id}: " . $e->getMessage());
                    $this->error("  ✗ Mail failed for alert #{$alert->id}: " . $e->getMessage());
                    // Do NOT deactivate — retry on next run
                }
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    /**
     * Determine which condition was met. An alert can have both high and low set;
     * we report whichever threshold was crossed (or both if both were hit).
     *
     * FIX: returns 'high', 'low', or 'both' — caller uses this for email subject.
     */
    protected function resolveTriggerType(Alert $alert, float $ltp): string
    {
        $highHit = $alert->high_price !== null && $ltp >= $alert->high_price;
        $lowHit  = $alert->low_price  !== null && $ltp <= $alert->low_price;

        if ($highHit && $lowHit) return 'both';
        if ($highHit) return 'high';
        return 'low';
    }

    protected function isTradingTime(): bool
    {
        $now      = Carbon::now('Asia/Dhaka');
        $day      = $now->dayOfWeek; // 0=Sun … 6=Sat
        $tradingDays = [
            Carbon::SUNDAY,
            Carbon::MONDAY,
            Carbon::TUESDAY,
            Carbon::WEDNESDAY,
            Carbon::THURSDAY
        ];

        if (! in_array($day, $tradingDays, true)) return false;

        $open  = $now->copy()->setTime(10, 0, 0);
        $close = $now->copy()->setTime(14, 30, 0);

        return $now->between($open, $close);
    }
}
