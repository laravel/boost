<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Detectors;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\AbstractDetector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\DetectionContext;

/**
 * Resolve the casing the project uses for enum case names; php/core punts this to "follow existing application Enum naming conventions", so recording the team's actual style helps a small model match it.
 */
class EnumKeyCasingDetector extends AbstractDetector
{
    public function id(): string
    {
        return 'enum-key-casing';
    }

    public function detect(DetectionContext $context): Collection
    {
        $tally = ['SCREAMING_SNAKE_CASE' => 0, 'PascalCase' => 0, 'camelCase' => 0];
        $enumFiles = [];

        foreach ($context->sampler->phpFiles($context->roots) as $file) {
            $path = $file->getPathname();

            if (! $context->sampler->contains($path, 'enum')) {
                continue;
            }

            $names = $this->enumCaseNames($context->sampler->contents($path));

            if ($names === []) {
                continue;
            }

            $enumFiles[] = $path;

            foreach ($names as $name) {
                $style = $this->classify($name);

                if ($style !== null) {
                    $tally[$style]++;
                }
            }
        }

        $dominant = $this->dominant($tally);

        if ($dominant === null) {
            return new Collection;
        }

        $style = $dominant['winner'];
        $glob = $this->globForFiles($enumFiles, $context->basePath);

        return new Collection([
            new Detection(
                id: $this->id(),
                title: "Name enum cases in {$style}",
                note: "This project names PHP enum cases in {$style} (detected in {$dominant['votes']}/{$dominant['total']} cases). Follow the same casing for new enums.",
                glob: $glob,
                confidence: $dominant['confidence'],
            ),
        ]);
    }

    /**
     * Collect enum case names via a token scan, so `case` labels inside `switch` bodies and the word
     * "enum" in comments/strings are never mistaken for enum cases.
     *
     * @return array<int, string>
     */
    protected function enumCaseNames(string $code): array
    {
        $tokens = @token_get_all($code);
        $names = [];
        $depth = 0;
        $pendingEnum = false;
        $enumBodyDepths = [];

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token)) {
                if ($token[0] === T_ENUM) {
                    $pendingEnum = true;
                } elseif ($token[0] === T_CASE && $enumBodyDepths !== [] && end($enumBodyDepths) === $depth) {
                    $name = $this->nextIdentifier($tokens, $i);

                    if ($name !== null) {
                        $names[] = $name;
                    }
                }

                continue;
            }

            if ($token === '{') {
                $depth++;

                if ($pendingEnum) {
                    $enumBodyDepths[] = $depth;
                    $pendingEnum = false;
                }
            } elseif ($token === '}') {
                if ($enumBodyDepths !== [] && end($enumBodyDepths) === $depth) {
                    array_pop($enumBodyDepths);
                }

                $depth--;
            }
        }

        return $names;
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     */
    protected function nextIdentifier(array $tokens, int $from): ?string
    {
        for ($i = $from + 1, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token)) {
                if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                return $token[0] === T_STRING ? $token[1] : null;
            }

            return null;
        }

        return null;
    }

    protected function classify(string $name): ?string
    {
        if (preg_match('/^[A-Z0-9]+(_[A-Z0-9]+)*$/', $name) === 1 && preg_match('/[A-Z]/', $name) === 1) {
            return 'SCREAMING_SNAKE_CASE';
        }

        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) === 1) {
            return 'PascalCase';
        }

        if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name) === 1) {
            return 'camelCase';
        }

        return null;
    }
}
