<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Rules\RuleRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:index-rules', 'Regenerate the project rules index from the rule files in .ai/rules')]
class RulesIndexCommand extends Command
{
    /** @var string */
    protected $signature = 'boost:index-rules';

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

        foreach ($repository->unindexedFiles() as $file) {
            $this->warn('Skipped '.$repository->relativePath($file).': no valid `paths` frontmatter.');
        }

        $path = $repository->writeIndex();

        $this->info('Regenerated '.$repository->relativePath($path).'.');

        return self::SUCCESS;
    }
}
