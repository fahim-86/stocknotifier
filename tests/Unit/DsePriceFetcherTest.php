<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DseTestScraperService;   // adjust to your actual class
use Illuminate\Support\Facades\Http;

class DsePriceFetcherTest
{
    /** @test */
    public function it_can_fetch_latest_prices_from_local_stub()
    {
        // 1. Fake all HTTP calls
        Http::fake([
            // The cookie jar request – return something that sets cookies
            'https://dsebd.org/' => Http::response('<html></html>', 200, [
                'Set-Cookie' => 'ASPSESSIONID=abcdef1234; Path=/; Domain=dsebd.org',
            ]),

            // The actual price page – feed our stub HTML
            'http://test-dse.local/latest_price' => Http::response(
                file_get_contents(base_path('tests/Stubs/latest_share_price_scroll.html')),
                200
            ),
        ]);

        // 2. Instantiate your service with the fake target URL
        //    (the original code uses $this->url – we set it to our fake URL)
        $fetcher = new DseTestScraperService('http://test-dse.local/latest_price');

        // 3. Call the method we want to test
        $prices = $fetcher->fetchOfflinePrices();

        return $prices->values();
    }
}
