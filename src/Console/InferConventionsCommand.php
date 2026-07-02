<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Install\Conventions\ConventionInspector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Rules\RuleRepository;

use function Laravel\Prompts\multiselect;

class InferConventionsCommand extends Command
{
    use DisplayHelper;

    protected $signature = 'boost:infer-conventions
        {--all : Record every inferred convention regardless of confidence}
        {--dry-run : Show what would be recorded without writing any files}
        {--diff : Show the rule content that would be added without writing any files}';

    protected $description = 'Infer project coding conventions and record the confirmed ones as path-scoped rules';

    public function handle(ConventionInspector $inspector, RuleRepository $repository): int
    {
        $this->displayBoostHeader('Conventions', config('app.name'));

        $detections = $inspector->inspect();

        if ($detections->isEmpty()) {
            $this->info('No clear conventions detected — nothing to record.');

            return self::SUCCESS;
        }

        $selected = $this->selectConventions($detections, (bool) $this->option('all'));

        if ($selected->isEmpty()) {
            $this->info('No conventions selected.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run') || $this->option('diff')) {
            $this->previewConventions($selected, $repository, (bool) $this->option('diff'));

            return self::SUCCESS;
        }

        foreach ($selected as $detection) {
            $location = $repository->write($detection->glob, $detection->title, $detection->note);

            $this->line('  '.$this->green('✓').' '.$detection->title.' → '.$repository->relativePath($location));
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Detection>  $selected
     */
    protected function previewConventions(Collection $selected, RuleRepository $repository, bool $diff): void
    {
        $this->line($this->dim('Dry run — no files were written.'));

        foreach ($selected as $detection) {
            $glob = $repository->normalizeGlob($detection->glob);

            $this->line('  '.$detection->title.' → '.$glob.'  '.$this->dim('['.$detection->provenance.']'));

            if ($diff) {
                $this->line($this->green('+ ## '.$detection->title));

                foreach (explode("\n", $detection->note) as $line) {
                    $this->line($this->green('+ '.$line));
                }

                $this->line('');
            }
        }
    }

    /**
     * @param  Collection<int, Detection>  $detections
     * @return Collection<int, Detection>
     */
    protected function selectConventions(Collection $detections, bool $all): Collection
    {
        if ($all) {
            return $detections->filter(fn (Detection $detection): bool => $detection->isInferred())->values();
        }

        if (! $this->input->isInteractive()) {
            return $detections->filter(fn (Detection $detection): bool => $detection->preselected)->values();
        }

        $options = $detections->mapWithKeys(fn (Detection $detection): array => [
            $detection->id => sprintf('%s  (%s · %s)', $detection->title, $this->provenanceLabel($detection), $detection->glob),
        ])->all();

        $defaults = $detections->filter(fn (Detection $detection): bool => $detection->preselected)->pluck('id')->all();

        $chosen = multiselect(
            label: 'Boost found these conventions and rules. Which should it record as project rules?',
            options: $options,
            default: $defaults,
            scroll: 15,
            hint: 'Inferred conventions are pre-checked; package and guideline rules are opt-in. Selected items are written to .ai/rules/.',
        );

        return $detections->filter(fn (Detection $detection): bool => in_array($detection->id, $chosen, true))->values();
    }

    protected function provenanceLabel(Detection $detection): string
    {
        return match ($detection->provenance) {
            Detection::PROVENANCE_INFERRED => (int) round($detection->confidence * 100).'% inferred',
            Detection::PROVENANCE_BOOST_GUIDELINE => 'Boost guideline',
            default => $detection->provenance,
        };
    }
}
