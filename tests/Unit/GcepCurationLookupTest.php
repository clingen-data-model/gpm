<?php

namespace Tests\Unit;

use App\Services\Api\ApiClient;
use App\Services\Api\GtApiService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GcepCurationLookupTest extends TestCase
{
    private function service(callable $callback): GtApiService
    {
        $client = $this->createStub(ApiClient::class);
        $client->method('post')->willReturnCallback(function ($path, $payload) use ($callback) {
            $this->assertSame('/bulk-lookup', $path);
            $this->assertSame('id', $payload['sortBy']);
            return new Response(new PsrResponse(200, [], json_encode($callback($payload))));
        });
        return new GtApiService($client);
    }

    public static function sizes(): array { return [[20], [100], [367], [1001]]; }

    #[DataProvider('sizes')]
    public function test_batches_symbols_and_traverses_all_curation_pages(int $count): void
    {
        $symbols = array_map(fn ($n) => 'GENE'.$n, range(1, $count));
        $calls = 0;
        $service = $this->service(function ($input) use (&$calls) {
            $calls++;
            $batch = explode(', ', $input['gene_symbol']);
            $this->assertLessThanOrEqual(100, count($batch));
            $rows = [];
            foreach ($batch as $symbol) {
                // Five curations for every gene, including when they span result pages.
                foreach (range(1, 5) as $n) $rows[] = ['gene_symbol' => $symbol, 'curation_id' => $symbol.'-'.$n];
            }
            return ['data' => array_slice($rows, ($input['page'] - 1) * 100, 100), 'meta' => [
                'current_page' => $input['page'], 'last_page' => (int) ceil(count($rows) / 100), 'total' => count($rows),
            ]];
        });
        $result = $service->lookupGcepGenesComplete($symbols);
        $this->assertCount($count * 5, $result['data']);
        $this->assertSame((int) ceil($count * 5 / 100), $calls);
    }

    public function test_empty_and_uncurated_genes(): void
    {
        $calls = 0;
        $service = $this->service(function () use (&$calls) {
            $calls++;
            return ['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]];
        });
        $this->assertSame(['data' => []], $service->lookupGcepGenesComplete([]));
        $this->assertSame(0, $calls);
        $this->assertSame(['data' => []], $service->lookupGcepGenesComplete(['UNCURATED']));
    }

    public static function failures(): array { return [['exception'], ['missing-meta'], ['duplicate'], ['changed-total'], ['truncated']]; }

    #[DataProvider('failures')]
    public function test_partial_results_are_never_returned(string $failure): void
    {
        $service = $this->service(function ($input) use ($failure) {
            if ($input['page'] === 2) {
                if ($failure === 'exception') throw new \RuntimeException('timeout');
                if ($failure === 'missing-meta') return ['data' => []];
            }
            return ['data' => [['gene_symbol' => 'A', 'curation_id' => $failure === 'duplicate' ? 'same' : 'id'.$input['page']]],
                'meta' => ['current_page' => $input['page'], 'last_page' => 2,
                    'total' => ($failure === 'changed-total' && $input['page'] === 2) || $failure === 'truncated' ? 3 : 2]];
        });
        $this->expectException(\RuntimeException::class);
        $service->lookupGcepGenesComplete(['A']);
    }
}
