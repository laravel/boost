<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Laravel\Boost\Console\Enums\Theme;
use Laravel\Prompts\Concerns\Colors;

use function Laravel\Prompts\note;

trait DisplayHelper
{
    use Colors;

    protected ?Theme $theme = null;

    protected function initTheme(?Theme $theme = null): void
    {
        $this->theme = $theme ?? Theme::random();
    }

    protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void
    {
        $this->initTheme($theme);

        $this->displayGradientLogo();
        $this->displayTagline($featureName);
        $this->displayNote($projectName);
    }

    protected function displayGradientLogo(): void
    {
        $lines = [
            '██████╗   ██████╗   ██████╗  ███████╗ ████████╗',
            '██╔══██╗ ██╔═══██╗ ██╔═══██╗ ██╔════╝ ╚══██╔══╝',
            '██████╔╝ ██║   ██║ ██║   ██║ ███████╗    ██║   ',
            '██╔══██╗ ██║   ██║ ██║   ██║ ╚════██║    ██║   ',
            '██████╔╝ ╚██████╔╝ ╚██████╔╝ ███████║    ██║   ',
            '╚═════╝   ╚═════╝   ╚═════╝  ╚══════╝    ╚═╝   ',
        ];

        $gradient = $this->theme->gradient();

        $this->newLine();

        foreach ($lines as $index => $line) {
            $this->output->writeln($this->ansi256Fg($gradient[$index], $line));
        }

        $this->newLine();
    }

    protected function displayTagline(string $featureName): void
    {
        $tagline = " ✦ Laravel Boost :: {$featureName} :: We Must Ship ✦ ";
        $this->output->writeln($this->displayBadge($tagline));
    }

    protected function displayNote(string $projectName): void
    {
        note("Let's give {$this->displayBadge($projectName)} a Boost");
    }

    protected function displayOutro(string $text, string $link = '', int $terminalWidth = 80): void
    {
        $visibleText = preg_replace('/\x1b\[[0-9;]*m|\x1b\]8;;[^\x07]*\x07|\x1b\]8;;\x1b\\\\/', '', $text.$link) ?? '';
        $visualWidth = mb_strwidth($visibleText);
        $paddingLength = (int) (floor(($terminalWidth - $visualWidth) / 2)) - 2;
        $padding = str_repeat(' ', max(0, $paddingLength));

        $this->output->writeln(
            "\e[48;5;{$this->theme->primary()}m\033[2K{$padding}\e[30m\e[1m{$text}{$link}\e[0m"
        );
        $this->newLine();
    }

    protected function ansi256Fg(int $color, string $text): string
    {
        return "\e[38;5;{$color}m{$text}\e[0m";
    }

    protected function displayBadge(string $text): string
    {
        return "\e[48;5;{$this->theme->primary()}m\e[30m\e[1m{$text}\e[0m";
    }

    protected function hyperlink(string $label, string $url): string
    {
        return "\033]8;;{$url}\007{$label}\033]8;;\033\\";
    }
}
