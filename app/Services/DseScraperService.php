<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class DseScraperService
{
    protected string $url = 'https://dsebd.org/latest_share_price_scroll_l.php';

    /**
     * Fetch the latest share prices from DSE.
     *
     * @return Collection of objects with 'trading_code' and 'ltp' properties.
     */

    public function fetchLatestPrices(): Collection
    {
        // 1. Get cookies from the main page
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
        if (!$response->successful()) return collect();

        $html = $response->body();
        $crawler = new Crawler($html);

        // 3. Locate the table (using class from full page)
        $table = $crawler->filter('.table-responsive.inner-scroll table.shares-table')->first();
        if (!$table->count()) {
            // try any responsive wrapper
            $table = $crawler->filter('.table-responsive table.shares-table')->first();
        }
        if (!$table->count()) {
            // last resort: get the first table that has rows
            $tables = $crawler->filter('table');
            $table = $tables->first();
        }

        $rows = $table->filter('tr');
        if ($rows->count() < 2) return collect();

        // 4. Detect columns
        $headerCells = $rows->first()->filter('th, td');
        $codeCol = null;
        $ltpCol  = null;
        $headerCells->each(function (Crawler $cell, int $i) use (&$codeCol, &$ltpCol) {
            $text = strtoupper(trim($cell->text()));
            if (!$text) {
                $label = $cell->attr('aria-label');
                $text  = $label ? strtoupper($label) : '';
            }
            if ($text === 'TRADING CODE' || str_contains($text, 'TRADING')) {
                $codeCol = $i;
            }
            if ($text && stripos($text, 'LTP') !== false) {
                $ltpCol = $i;
            }
        });
        $codeCol = $codeCol ?? 1;
        $ltpCol  = $ltpCol  ?? 2;

        // 5. Extract data
        $data = collect();
        $rows->each(function (Crawler $row, int $i) use ($codeCol, $ltpCol, $data) {
            if ($i === 0) return;
            $cells = $row->filter('td');
            if ($cells->count() <= max($codeCol, $ltpCol)) return;
            $code = trim($cells->eq($codeCol)->text());
            $ltp  = trim($cells->eq($ltpCol)->text());
            if (empty($code) || empty($ltp)) return;
            $cleanLtp = preg_replace('/[^0-9.-]/', '', $ltp);
            if (!is_numeric($cleanLtp)) return;
            $data->push((object)[
                'trading_code' => $code,
                'ltp'          => (float) $cleanLtp,
            ]);
        });

        // dump("Fetched {$data->count()} price entries from DSE.");
        return $data->values();
    }
    
    
    // protected string $url;

    // public function __construct(string $url = "")
    // {
    //     $this->url = $url ?? 'https://dsebd.org/latest_share_price_scroll.php.na'; // real URL
    // }

    /**
     * Try to find the column numbers by matching header text.
     *
     * @param Crawler $headerCells
     * @return array ['code' => index, 'ltp' => index] or null if not found
     */
    protected function mapColumns(Crawler $headerCells): ?array
    {
        $mapping = ['code' => null, 'ltp' => null];

        $headerCells->each(function (Crawler $cell, int $idx) use (&$mapping) {
            $text = strtolower(trim($cell->text()));
            if (in_array($text, ['trading code', 'code', 'scrip', 'symbol'])) {
                $mapping['code'] = $idx;
            }
            if (in_array($text, ['ltp', 'last trade', 'last price', 'close', 'ltp*'])) {
                $mapping['ltp'] = $idx;
            }
        });

        return ($mapping['code'] !== null && $mapping['ltp'] !== null) ? $mapping : null;
    }
}
