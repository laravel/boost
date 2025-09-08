<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Laravel\Boost\Middleware\InjectBoost;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    $this->app['view']->addNamespace('test', __DIR__.'/../../fixtures');
});

function createMiddlewareResponse($response): SymfonyResponse
{
    $middleware = new InjectBoost();
    $request = new Request();
    $next = fn ($request) => $response;

    return $middleware->handle($request, $next);
}

it('preserves the original view response type', function () {
    Route::get('injection-test', function () {
        return view('test::injection-test');
    })->middleware(InjectBoost::class);

    $response = $this->get('injection-test');

    $response->assertViewIs('test::injection-test')
        ->assertSee('browser-logger-active')
        ->assertSee('Browser logger active (MCP server detected).');
});

it('does not inject for special response types', function ($responseType, $responseFactory) {
    $response = $responseFactory();
    $result = createMiddlewareResponse($response);

    expect($result)->toBeInstanceOf($responseType);
})->with([
    'streamed' => [StreamedResponse::class, fn () => new StreamedResponse()],
    'json' => [JsonResponse::class, fn () => new JsonResponse(['data' => 'test'])],
    'redirect' => [RedirectResponse::class, fn () => new RedirectResponse('http://example.com')],
    'binary' => [BinaryFileResponse::class, function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        return new BinaryFileResponse(new SplFileInfo($tempFile));
    }],
]);

it('does not inject when conditions are not met', function ($scenario, $responseFactory, $assertion) {
    $response = $responseFactory();
    $result = createMiddlewareResponse($response);

    $assertion($result);
})->with([
    'non-html content type' => [
        'scenario',
        fn () => (new Response('test'))->withHeaders(['content-type' => 'application/json']),
        fn ($result) => expect($result->getContent())->toBe('test'),
    ],
    'missing html skeleton' => [
        'scenario',
        fn () => (new Response('test'))->withHeaders(['content-type' => 'text/html']),
        fn ($result) => expect($result->getContent())->toBe('test'),
    ],
    'already injected' => [
        'scenario',
        fn () => (new Response('<html><head><title>Test</title></head><body><div class="browser-logger-active"></div></body></html>'))
            ->withHeaders(['content-type' => 'text/html']),
        fn ($result) => expect($result->getContent())->toContain('browser-logger-active'),
    ],
]);

it('injects script in html responses', function ($html) {
    $response = new Response($html);
    $response->headers->set('content-type', 'text/html');

    $result = createMiddlewareResponse($response);

    expect($result->getContent())->toContain('<script id="browser-logger-active">');
})->with([
    'with head and body tags' => '<html><head><title>Test</title></head><body></body></html>',
    'without head/body tags' => '<html>Test</html>',
]);
