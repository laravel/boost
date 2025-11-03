<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Cli;

use InvalidArgumentException;

class DisplayHelper
{
    private const UNICODE_TOP_LEFT = '╭';

    private const UNICODE_TOP_RIGHT = '╮';

    private const UNICODE_BOTTOM_LEFT = '╰';

    private const UNICODE_BOTTOM_RIGHT = '╯';

    private const UNICODE_HORIZONTAL = '─';

    private const UNICODE_VERTICAL = '│';

    private const UNICODE_CROSS = '┼';

    private const UNICODE_TOP_T = '┬';

    private const UNICODE_BOTTOM_T = '┴';

    private const UNICODE_LEFT_T = '├';

    private const UNICODE_RIGHT_T = '┤';

    private const BORDER_TOP = 'top';

    private const BORDER_MIDDLE = 'middle';

    private const BORDER_BOTTOM = 'bottom';

    private const CELL_PADDING = 2;

    private const GRID_CELL_PADDING = 4;

    private const ANSI_BOLD = "\e[1m";

    private const ANSI_RESET = "\e[0m";

    private const SPACE = ' ';

    /**
     * @param  array<int, array<int|string, mixed>>  $data
     */
    public static function datatable(array $data, int $maxWidth = 80): void
    {
        if ($data === []) {
            return;
        }

        $columnWidths = self::calculateColumnWidths($data);
        $columnWidths = array_map(fn (int $width): int => $width + self::CELL_PADDING, $columnWidths);

        [$leftChar, $rightChar, $joinChar] = self::getBorderChars(self::BORDER_TOP);
        echo self::buildBorder($columnWidths, $leftChar, $rightChar, $joinChar).PHP_EOL;

        $rowCount = 0;
        foreach ($data as $row) {
            echo self::buildDataRow($row, $columnWidths).PHP_EOL;

            if ($rowCount < count($data) - 1) {
                [$leftChar, $rightChar, $joinChar] = self::getBorderChars(self::BORDER_MIDDLE);
                echo self::buildBorder($columnWidths, $leftChar, $rightChar, $joinChar).PHP_EOL;
            }

            $rowCount++;
        }

        [$leftChar, $rightChar, $joinChar] = self::getBorderChars(self::BORDER_BOTTOM);
        echo self::buildBorder($columnWidths, $leftChar, $rightChar, $joinChar).PHP_EOL;
    }

    /**
     * @param  array<int, string>  $items
     */
    public static function grid(array $items, int $maxWidth = 80): void
    {
        if ($items === []) {
            return;
        }

        $maxWidth -= 2;  // account for grid margins
        $maxItemLength = max(array_map(mb_strlen(...), $items));
        $cellWidth = $maxItemLength + self::GRID_CELL_PADDING;
        $cellsPerRow = max(1, (int) floor(($maxWidth - 1) / ($cellWidth + 1)));
        $rows = array_chunk($items, $cellsPerRow);

        $cellWidths = array_fill(0, $cellsPerRow, $cellWidth);

        [$leftChar, $rightChar, $joinChar] = self::getBorderChars(self::BORDER_TOP);
        echo self::SPACE.self::buildBorder($cellWidths, $leftChar, $rightChar, $joinChar).PHP_EOL;

        $rowCount = 0;
        foreach ($rows as $row) {
            echo self::SPACE.self::buildGridRow($row, $cellWidth, $cellsPerRow).PHP_EOL;

            if ($rowCount < count($rows) - 1) {
                [$leftChar, $rightChar, $joinChar] = self::getBorderChars(self::BORDER_MIDDLE);
                echo self::SPACE.self::buildBorder($cellWidths, $leftChar, $rightChar, $joinChar).PHP_EOL;
            }

            $rowCount++;
        }

        [$leftChar, $rightChar, $joinChar] = self::getBorderChars(self::BORDER_BOTTOM);
        echo self::SPACE.self::buildBorder($cellWidths, $leftChar, $rightChar, $joinChar).PHP_EOL;
    }

    private static function getBorderChars(string $type): array
    {
        return match ($type) {
            self::BORDER_TOP => [self::UNICODE_TOP_LEFT, self::UNICODE_TOP_RIGHT, self::UNICODE_TOP_T],
            self::BORDER_MIDDLE => [self::UNICODE_LEFT_T, self::UNICODE_RIGHT_T, self::UNICODE_CROSS],
            self::BORDER_BOTTOM => [self::UNICODE_BOTTOM_LEFT, self::UNICODE_BOTTOM_RIGHT, self::UNICODE_BOTTOM_T],
            default => throw new InvalidArgumentException('Border type should be valid'),
        };
    }

    /**
     * @param  array<int, array<int|string, mixed>>  $data
     * @return array<int, int>
     */
    private static function calculateColumnWidths(array $data): array
    {
        $columnWidths = [];
        foreach ($data as $row) {
            foreach ($row as $colIndex => $cell) {
                $length = mb_strlen((string) $cell);
                $columnWidths[$colIndex] = max($columnWidths[$colIndex] ?? 0, $length);
            }
        }

        return $columnWidths;
    }

    /**
     * @param  array<int, int>  $widths
     */
    private static function buildBorder(array $widths, string $leftChar, string $rightChar, string $joinChar): string
    {
        $border = $leftChar;
        foreach ($widths as $index => $width) {
            $border .= str_repeat(self::UNICODE_HORIZONTAL, $width);
            if ($index < count($widths) - 1) {
                $border .= $joinChar;
            }
        }

        return $border.$rightChar;
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @param  array<int, int>  $columnWidths
     */
    private static function buildDataRow(array $row, array $columnWidths): string
    {
        $line = self::UNICODE_VERTICAL;
        $colIndex = 0;
        foreach ($row as $cell) {
            $cellStr = ($colIndex === 0) ? self::ANSI_BOLD.$cell.self::ANSI_RESET : $cell;
            $padding = $columnWidths[$colIndex] - mb_strlen((string) $cell);
            $line .= self::SPACE.$cellStr.str_repeat(self::SPACE, $padding - 1).self::UNICODE_VERTICAL;
            $colIndex++;
        }

        return $line;
    }

    /**
     * @param  array<int, string>  $row
     */
    private static function buildGridRow(array $row, int $cellWidth, int $cellsPerRow): string
    {
        $line = self::UNICODE_VERTICAL;

        $cells = array_map(
            fn (int $index): string => self::formatGridCell($row[$index] ?? '', $cellWidth),
            range(0, $cellsPerRow - 1)
        );

        return $line.(implode(self::UNICODE_VERTICAL, $cells).self::UNICODE_VERTICAL);
    }

    private static function formatGridCell(string $item, int $cellWidth): string
    {
        if ($item === '' || $item === '0') {
            return str_repeat(self::SPACE, $cellWidth);
        }

        $padding = $cellWidth - mb_strlen($item) - 2;

        return self::SPACE.$item.str_repeat(self::SPACE, $padding + 1);
    }
}
