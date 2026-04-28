<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\DseScraper;  // <-- Adjust to your actual class path
use App\Services\DseScraperService;
use Illuminate\Support\Collection;

class DseScraperTest extends TestCase
{
    /** @test */
    public function it_extracts_trading_codes_and_ltp_correctly()
    {
        // 1. Load the stub HTML
        $html = file_get_contents(base_path('tests/Stubs/latest_share_price_scroll.html'));

        // 2. Fake the HTTP response for the exact URL
        Http::fake([
            'dsebd.org/*' => Http::response($html, 200),
        ]);

        // 3. Instantiate your scraper (adjust constructor if needed)
        $scraper = new DseScraperService(); // or via app()->make(...)
        // 4. Execute the method
        $result = $scraper->fetchLatestPrices();

        // 5. Assertions
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThan(0, $result->count(), 'Expected at least one row');

        // Check first row
        $first = $result->first();
        $this->assertIsObject($first);
        $this->assertTrue(property_exists($first, 'trading_code'), 'Expected trading_code property');
        $this->assertTrue(property_exists($first, 'ltp'), 'Expected ltp property');

        // Check a specific known code from your stub
        $codes = $result->pluck('trading_code')->toArray();
        $this->assertContains('1JANATAMF', $codes);

        // Verify LTP is numeric
        $firstLtp = $first->ltp;
        $this->assertIsFloat($firstLtp);
        $this->assertEquals(3.0, $firstLtp); // From your snippet: <td>3</td>
    }
}
