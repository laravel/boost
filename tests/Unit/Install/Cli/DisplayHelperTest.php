<?php

declare(strict_types=1);

use Laravel\Boost\Install\Cli\DisplayHelper;

describe('DisplayHelper tests', function (): void {
    describe('datatable tests', function (): void {
        it('returns early for empty data', function (): void {
            ob_start();
            DisplayHelper::datatable([]);
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('displays a simple single row table', function (): void {
            ob_start();
            DisplayHelper::datatable([
                ['Name', 'Age'],
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('Name', 'Age', '╭', '╮', '╰', '╯');
        });

        it('displays a multi-row table', function (): void {
            ob_start();
            DisplayHelper::datatable([
                ['Name', 'Age', 'City'],
                ['John', '25', 'New York'],
                ['Jane', '30', 'London'],
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('Name', 'John', 'Jane', '├', '┤', '┼');
        });

        it('handles different data types in cells', function (): void {
            ob_start();
            DisplayHelper::datatable([
                ['String', 'Number', 'Boolean'],
                ['text', '123', 'true'],
                ['another', '456', 'false'],
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('text', '123', 'true', 'another', '456');
        });

        it('applies bold formatting to first column', function (): void {
            ob_start();
            DisplayHelper::datatable([
                ['Header1', 'Header2'],
                ['Value1', 'Value2'],
            ]);
            $output = ob_get_clean();

            expect($output)->toContain("\e[1mHeader1\e[0m", "\e[1mValue1\e[0m")
                ->and($output)->not->toContain("\e[1mHeader2\e[0m");
        });

        it('handles unicode characters properly', function (): void {
            ob_start();
            DisplayHelper::datatable([
                ['名前', 'Émile'],
                ['測試', 'café'],
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('名前', 'Émile', '測試', 'café');
        });
    });

    describe('grid test', function (): void {
        it('returns early for empty items', function (): void {
            ob_start();
            DisplayHelper::grid([]);
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('displays single item grid', function (): void {
            ob_start();
            DisplayHelper::grid(['Item1']);
            $output = ob_get_clean();

            expect($output)->toContain('Item1', '╭', '╮', '╰', '╯');
        });

        it('displays multiple items in grid', function (): void {
            ob_start();
            DisplayHelper::grid(['Item1', 'Item2', 'Item3', 'Item4']);
            $output = ob_get_clean();

            expect($output)->toContain('Item1', 'Item2', 'Item3', 'Item4');
        });

        it('handles items of different lengths', function (): void {
            ob_start();
            DisplayHelper::grid(['Short', 'Very Long Item Name', 'Med']);
            $output = ob_get_clean();

            expect($output)->toContain('Short', 'Very Long Item Name', 'Med');
        });

        it('respects column width parameter', function (): void {
            ob_start();
            DisplayHelper::grid(['Item1', 'Item2'], 40);
            $output = ob_get_clean();

            expect($output)->toContain('Item1', 'Item2');
        });

        it('handles unicode characters in grid', function (): void {
            ob_start();
            DisplayHelper::grid(['測試', 'café', '🚀']);
            $output = ob_get_clean();

            expect($output)->toContain('測試', 'café', '🚀');
        });

        it('fills empty cells when items do not fill complete rows', function (): void {
            ob_start();
            DisplayHelper::grid(['Item1', 'Item2', 'Item3']);
            $output = ob_get_clean();

            $lines = explode("\n", $output);
            $dataLine = '';
            foreach ($lines as $line) {
                if (str_contains($line, 'Item1')) {
                    $dataLine = $line;
                    break;
                }
            }

            expect($dataLine)->toContain('│')
                ->and(substr_count($dataLine, '│'))->toBeGreaterThan(2);
        });
    });
});
