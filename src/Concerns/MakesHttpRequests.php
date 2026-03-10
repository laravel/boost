<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait MakesHttpRequests
{
    public function client(): PendingRequest
    {
        $client = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:140.0) Gecko/20100101 Firefox/140.0 Laravel Boost',
        ]);

        $proxy = $this->getProxyConfig();

        if ($proxy !== []) {
            $client = $client->withOptions(['proxy' => $proxy]);
        }

        // Disable SSL verification for local development URLs and testing
        if (app()->environment(['local', 'testing']) || str_contains((string) config('boost.hosted.api_url', ''), '.test')) {
            return $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Get proxy configuration from environment variables.
     *
     * @return array<string, string|array<int, string>>
     */
    protected function getProxyConfig(): array
    {
        $proxy = [];

        $httpProxy = getenv('HTTP_PROXY') ?: getenv('http_proxy');

        if ($httpProxy !== false && $httpProxy !== '') {
            $proxy['http'] = $httpProxy;
        }

        $httpsProxy = getenv('HTTPS_PROXY') ?: getenv('https_proxy');

        if ($httpsProxy !== false && $httpsProxy !== '') {
            $proxy['https'] = $httpsProxy;
        }

        $noProxy = getenv('NO_PROXY') ?: getenv('no_proxy');

        if ($noProxy !== false && $noProxy !== '') {
            $proxy['no'] = array_map('trim', explode(',', $noProxy));
        }

        return $proxy;
    }

    public function get(string $url): Response
    {
        return $this->client()->get($url);
    }

    /**
     * @param  array<string, mixed>  $json
     */
    public function json(string $url, array $json): Response
    {
        return $this->client()->asJson()->post($url, $json);
    }
}
