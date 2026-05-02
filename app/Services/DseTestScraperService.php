<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Unit\DsePriceFetcherTest;

class DseTestScraperService
{
    protected string $baseUrl  = 'https://dsebd.org/';
    protected string $url;

    public function __construct(string $url = "")
    {
        $this->url = $url ?? 'https://dsebd.org/latest_share_price_scroll.php.na'; // real URL
        dump("DseTestScraperService initialized with URL: {$this->url}");
    }

    public function fetchOfflinePrices(): Collection
    {
        try {
            $jarResponse = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            ])->get('https://dsebd.org/');

            // Convert Cookie objects to a simple key → value array
            $cookies = [];
            foreach ($jarResponse->cookies() as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }

            // 2. Request the target page, passing the cookies
            $response = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Referer'         => 'https://dsebd.org/',
            ])
                ->withCookies($cookies, 'dsebd.org')   // now it's a clean associative array
                ->timeout(15)
                ->get($this->url);

            if (! $response->successful()) {
                Log::warning('DSE scraper: non-200 response', ['status' => $response->status()]);
                return collect();
            }

            return $this->parseHtml($response->body());
        } catch (\Throwable $e) {
            Log::error('DSE scraper exception: ' . $e->getMessage());
            return collect();
        }
    }

    protected function parseHtml(string $html): Collection
    {
        $crawler = new Crawler($html);

        // Try the specific table first, then fall back to first available table
        $table = $crawler->filter('.table-responsive table.shares-table')->first();
        if (! $table->count()) {
            $table = $crawler->filter('table')->first();
        }
        if (! $table->count()) {
            Log::warning('DSE scraper: no table found in response');
            return collect();
        }

        $rows = $table->filter('tr');
        if ($rows->count() < 2) {
            return collect();
        }

        // Detect column positions from header row
        [$codeCol, $ltpCol] = $this->detectColumns($rows->first()->filter('th, td'));

        $data = collect();

        $rows->each(function (Crawler $row, int $i) use ($codeCol, $ltpCol, $data) {
            if ($i === 0) return; // skip header

            $cells = $row->filter('td');
            if ($cells->count() <= max($codeCol, $ltpCol)) return;

            $code     = trim($cells->eq($codeCol)->text(''));
            $ltpRaw   = trim($cells->eq($ltpCol)->text(''));
            $cleanLtp = preg_replace('/[^0-9.]/', '', $ltpRaw);

            if (empty($code) || ! is_numeric($cleanLtp) || (float) $cleanLtp <= 0) return;

            $data->push((object) [
                'trading_code' => $code,
                'ltp'          => (float) $cleanLtp,
            ]);
        });

        return $data->values();
    }

    protected function detectColumns(Crawler $headerCells): array
    {
        $codeCol = null;
        $ltpCol  = null;

        $headerCells->each(function (Crawler $cell, int $i) use (&$codeCol, &$ltpCol) {
            $text = strtoupper(trim($cell->text('')));
            if (! $text && $cell->attr('aria-label')) {
                $text = strtoupper($cell->attr('aria-label'));
            }

            if (str_contains($text, 'TRADING') || $text === 'CODE' || $text === 'SCRIP') {
                $codeCol = $i;
            }
            if (str_contains($text, 'LTP') || str_contains($text, 'LAST')) {
                $ltpCol = $i;
            }
        });

        // Sensible defaults if detection fails
        return [$codeCol ?? 1, $ltpCol ?? 2];
    }

    protected function browserHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                . 'Chrome/124.0.0.0 Safari/537.36',
            'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];
    }
}
