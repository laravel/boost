<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListArtisanCommands extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'List all available Artisan commands registered in this application.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $commands = Artisan::all();

        $commandList = [];
        foreach ($commands as $name => $command) {
            /** @var Command $command */
            $commandList[] = [
                'name' => $name,
                'description' => $command->getDescription(),
            ];
        }

        // Sort alphabetically by name for determinism.
        usort($commandList, fn (array $firstCommand, array $secondCommand): int => strcmp((string) $firstCommand['name'], (string) $secondCommand['name']));

        return Response::json($commandList);
    }
}
