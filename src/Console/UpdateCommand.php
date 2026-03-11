<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines & skills to the latest guidance')]
class UpdateCommand extends Command
{
    public function handle(Config $config): int
    {
        if (! $config->isValid() || empty($config->getAgents())) {
            $this->error('Please set up Boost with [php artisan boost:install] first.');

            return self::FAILURE;
        }

        $guidelines = $config->getGuidelines();
        $hasSkills = $config->hasSkills();

        if (! $guidelines && ! $hasSkills) {
            return self::SUCCESS;
        }

        if ($hasSkills) {
            $this->checkForNewSkills($config);
        }

        $this->callSilently(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => $guidelines,
            '--skills' => $config->hasSkills(),
        ]);

        $this->info('Boost guidelines and skills updated successfully.');

        return self::SUCCESS;
    }

    protected function checkForNewSkills(Config $config): void
    {
        if (! $this->isInteractiveMode()) {
            return;
        }

        $guidelineConfig = new GuidelineConfig;
        $guidelineConfig->aiGuidelines = $config->getPackages();
        $guidelineConfig->hasSkills = true;

        $availableSkills = $this->resolveAvailableSkills($guidelineConfig);

        $installedSkillKeys = $config->getSkills();

        $newSkills = $availableSkills->filter(
            fn (Skill $skill, string $key): bool => ! in_array($key, $installedSkillKeys, true)
        );

        if ($newSkills->isEmpty()) {
            return;
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'New skills discovered! Which would you like to add?',
            options: $newSkills
                ->mapWithKeys(fn (Skill $skill, string $key): array => [$key => $skill->displayName()])
                ->toArray(),
            scroll: 10,
            required: false,
            hint: 'Press Enter to skip, or select skills to add them',
        );

        if ($selected === []) {
            $this->warn('No new skills selected.');

            return;
        }

        $config->setSkills(array_merge($installedSkillKeys, $selected));
    }

    protected function resolveAvailableSkills(GuidelineConfig $config): Collection
    {
        return app(SkillComposer::class)->config($config)->skills();
    }

    protected function isInteractiveMode(): bool
    {
        return $this->input?->isInteractive() ?? false; // @phpstan-ignore-line
    }
}
