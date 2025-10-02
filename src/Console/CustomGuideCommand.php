<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:custom', 'Create a custom guide based on your requirements.')]

class CustomGuideCommand extends Command
{
    protected $signature = 'boost:custom {name : The name of the custom guide}';

    public function handle(): void
    {
        $name = $this->argument('name');
        assert(is_string($name), 'Name argument must be a string');

        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        assert(is_string($sanitizedName), 'Sanitized name must be a string');

        if ($sanitizedName === '' || $sanitizedName === '0') {
            $this->components->error('Invalid guide name. Use only alphanumeric characters, hyphens, and underscores.');

            return;
        }

        $fileName = $sanitizedName.'.blade.php';
        $directory = base_path('.ai/guidelines');
        $filePath = $directory.'/'.$fileName;

        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            $this->components->error("Failed to create directory: {$directory}");

            return;
        }

        $realDirectory = realpath($directory);
        assert(is_string($realDirectory), 'Real directory path must be a string');
        $realFilePath = $realDirectory.'/'.basename($filePath);

        if (! str_starts_with($realFilePath, $realDirectory)) {
            $this->components->error('Invalid file path.');

            return;
        }

        if (file_exists($filePath)) {
            $this->components->error("A guideline with the name '{$sanitizedName}' already exists.");

            return;
        }

        $titleRaw = $this->ask('* What is the title of your custom guide?');
        $descriptionRaw = $this->ask('* Describe the purpose of this guide (optional)', '');

        $title = is_string($titleRaw) ? mb_substr($titleRaw, 0, 200) : '';
        $description = is_string($descriptionRaw) ? mb_substr($descriptionRaw, 0, 500) : '';

        // Create the file with basic structure
        $content = "# {$title}

";

        if ($description !== '' && $description !== '0') {
            $content .= "## {$description}
";
        }

        $content .= '
<!-- Add your custom guidelines here -->
';

        if (file_put_contents($filePath, $content) === false) {
            $this->components->error("Failed to create file: {$fileName}");

            return;
        }

        $this->components->info("Custom guide created: .ai/{$fileName}");
    }
}
