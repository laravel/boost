<?php

declare(strict_types=1);

namespace Laravel\Boost\Prompts\Themes\Default;

use Laravel\Boost\Prompts\Grid;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class GridRenderer extends Renderer
{
    public function __invoke(Grid $grid): string
    {
        if ($grid->items === []) {
            return (string) $this;
        }

        $items = collect($grid->items);
        $maxWidth = $grid->maxWidth - 2;
        $cellWidth = $items->max(fn ($item): int => mb_strlen((string) $item)) + 4;
        $maxColumns = max(1, (int) floor(($maxWidth - 1) / ($cellWidth + 1)));
        $columns = $this->balancedColumnCount($items->count(), $maxColumns);

        $rows = $items->chunk($columns)
            ->map(fn ($chunk) => $chunk->pad($columns, '')->values()->all())
            ->flatMap(fn ($row, $index): array => $index > 0 ? [new TableSeparator, $row] : [$row])
            ->all();

        $output = new BufferedConsoleOutput;

        (new SymfonyTable($output))
            ->setRows($rows)
            ->setStyle($this->tableStyle())
            ->setColumnMaxWidth(0, $cellWidth)
            ->render();

        collect(explode(PHP_EOL, trim($output->content(), PHP_EOL)))
            ->each(fn ($line): \Laravel\Prompts\Themes\Default\Renderer => $this->line(' '.$line));

        return (string) $this;
    }

    private function balancedColumnCount(int $itemCount, int $maxColumns): int
    {
        if ($itemCount <= $maxColumns) {
            return $itemCount;
        }

        for ($cols = $maxColumns; $cols >= 1; $cols--) {
            $remainder = $itemCount % $cols;

            if ($remainder === 0 || $remainder >= (int) ceil($cols / 2)) {
                return $cols;
            }
        }

        return $maxColumns;
    }

    private function tableStyle(): TableStyle
    {
        return (new TableStyle)
            ->setHorizontalBorderChars('─')
            ->setVerticalBorderChars('│', '│')
            ->setCellRowFormat('<fg=default>%s</>')
            ->setCrossingChars(
                cross: '┼',
                topLeft: '',
                topMid: '',
                topRight: '',
                midRight: '┤',
                bottomRight: '┘</>',
                bottomMid: '┴',
                bottomLeft: '└',
                midLeft: '├',
                topLeftBottom: '┌',
                topMidBottom: '┬',
                topRightBottom: '┐',
            )
            ->setPadType(STR_PAD_RIGHT);
    }
}
