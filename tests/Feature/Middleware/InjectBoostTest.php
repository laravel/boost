<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Testing\TestResponse;
use Laravel\Boost\Middleware\InjectBoost;
use Pest\Expectation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function (): void {
    $this->app['view']->addNamespace('test', __DIR__.'/../../Fixtures');
});

function createMiddlewareResponse($response): SymfonyResponse
{
    $middleware = new InjectBoost;
    $request = new Request;
    $next = fn ($request) => $response;

    return $middleware->handle($request, $next);
}

it('preserves the original view response type', function (): void {
    Route::get('injection-test', fn (): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory => view('test::injection-test'))->middleware(InjectBoost::class);

    $response = $this->get('injection-test');

    $response->assertViewIs('test::injection-test')
        ->assertSee('browser-logger-active')
        ->assertSee('Browser logger active (MCP server detected).');
});

it('does not inject for special response types', function ($responseType, $responseFactory): void {
    $response = $responseFactory();
    $result = createMiddlewareResponse($response);

    expect($result)->toBeInstanceOf($responseType);
})->with([
    'streamed' => [StreamedResponse::class, fn (): \Symfony\Component\HttpFoundation\StreamedResponse => new StreamedResponse],
    'json' => [JsonResponse::class, fn (): \Symfony\Component\HttpFoundation\JsonResponse => new JsonResponse(['data' => 'test'])],
    'redirect' => [RedirectResponse::class, fn (): \Symfony\Component\HttpFoundation\RedirectResponse => new RedirectResponse('http://example.com')],
    'binary' => [BinaryFileResponse::class, function (): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        return new BinaryFileResponse(new SplFileInfo($tempFile));
    }],
]);

it('does not inject when conditions are not met', function ($scenario, $responseFactory, $assertion): void {
    $response = $responseFactory();
    $result = createMiddlewareResponse($response);

    $assertion($result);
})->with([
    'non-html content type' => [
        'scenario',
        fn () => (new Response('test'))->withHeaders(['content-type' => 'application/json']),
        fn ($result): Expectation => expect($result->getContent())->toBe('test'),
    ],
    'missing html skeleton' => [
        'scenario',
        fn () => (new Response('test'))->withHeaders(['content-type' => 'text/html']),
        fn ($result): Expectation => expect($result->getContent())->toBe('test'),
    ],
    'already injected' => [
        'scenario',
        fn () => (new Response('<html><head><title>Test</title></head><body><div class="browser-logger-active"></div></body></html>'))
            ->withHeaders(['content-type' => 'text/html']),
        fn ($result): Expectation => expect($result->getContent())->toContain('browser-logger-active'),
    ],
]);

it('injects script in html responses', function ($html): void {
    $response = new Response($html);
    $response->headers->set('content-type', 'text/html');

    $result = createMiddlewareResponse($response);

    expect($result->getContent())->toContain('<script id="browser-logger-active">');
})->with([
    'with head and body tags' => '<html><head><title>Test</title></head><body></body></html>',
    'without head/body tags' => '<html>Test</html>',
]);

it('handles CSP nonce attribute correctly', function ($nonce, $assertions): void {
    if ($nonce) {
        Vite::useCspNonce($nonce);
    }

    Route::get('injection-test', fn (): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory => view('test::injection-test'))
        ->middleware(InjectBoost::class);

    $response = $this->get('injection-test')->assertViewIs('test::injection-test');

    $assertions($response);
})->with([
    'with CSP nonce configured' => [
        'test-nonce',
        fn (TestResponse $response) => $response
            ->assertSee('nonce="test-nonce"', false)
            ->assertSee('id="browser-logger-active"', false),
    ],
    'without CSP nonce configured' => [
        null,
        fn (TestResponse $response) => $response
            ->assertSee('<script id="browser-logger-active">', false)
            ->assertDontSee('nonce=', false)
            ->assertDontSee('test-nonce', false),
    ],
]);
