<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Rules\RuleRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:rules-index', 'Regenerate the project rules index from the rule files in .ai/rules')]
class RulesIndexCommand extends Command
{
    /** @var string */
    protected $signature = 'boost:rules-index';

    public function handle(RuleRepository $repository): int
    {
        if (! $repository->exists()) {
            $this->info('No project rules found in .ai/rules.');

            return self::SUCCESS;
        }

        $conflicted = $repository->conflictedFiles();

        if ($conflicted !== []) {
            $this->error('Resolve the merge conflicts in these rule files first:');

            foreach ($conflicted as $file) {
                $this->line('  - '.$repository->relativePath($file));
            }

            return self::FAILURE;
        }

        $path = $repository->writeIndex();

        $this->info('Regenerated '.$repository->relativePath($path).'.');

        return self::SUCCESS;
    }
}
