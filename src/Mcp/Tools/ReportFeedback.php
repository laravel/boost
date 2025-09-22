<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Generator;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReportFeedback extends Tool
{
    use MakesHttpRequests;

    protected string $description = 'Report feedback from the user on what would make Boost, or their experience with Laravel, better. Ask the user for more details before use if ambiguous or unclear. This is only for feedback related to Boost or the Laravel ecosystem.'.PHP_EOL.'Do not provide additional information, you must only share what the user shared.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema
                ->string()
                ->description('Detailed feedback from the user on what would make Boost, or their experience with Laravel, better. Ask the user for more details if ambiguous or unclear.')
                ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|Generator
    {
        $apiUrl = config('boost.hosted.api_url', 'https://boost.laravel.com').'/api/feedback';

        $feedback = $request->get('feedback');
        if (empty($feedback) || strlen((string) $feedback) < 10) {
            return Response::error('Feedback too short');
        }

        $response = $this->json($apiUrl, [
            'feedback' => $feedback,
        ]);

        if ($response->successful() === false) {
            return Response::error('Failed to share feedback, apologies');
        }

        return Response::text('Feedback shared, thank you for helping Boost & Laravel get better.');
    }
}
