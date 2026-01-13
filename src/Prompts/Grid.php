<?php

declare(strict_types=1);

namespace Laravel\Boost\Prompts;

use Illuminate\Support\Collection;
use Laravel\Boost\Prompts\Themes\Default\GridRenderer;
use Laravel\Prompts\Prompt;

class Grid extends Prompt
{
    /**
     * The grid items.
     *
     * @var array<int, string>
     */
    public array $items;

    /**
     * The maximum width for the grid.
     */
    public int $maxWidth;

    /**
     * Create a new Grid instance.
     *
     * @param  array<int, string>|Collection<int, string>  $items
     */
    public function __construct(array|Collection $items = [], ?int $maxWidth = null)
    {
        $this->items = $items instanceof Collection ? $items->all() : $items;
        $this->maxWidth = $maxWidth ?? static::terminal()->cols() ?: 80;
    }

    /**
     * Register the Grid renderer with Laravel Prompts.
     */
    public static function register(): void
    {
        static::$themes['default'][self::class] = GridRenderer::class;
    }

    /**
     * Display the grid.
     */
    public function display(): void
    {
        $this->prompt();
    }

    /**
     * Render the grid prompt.
     */
    public function prompt(): bool
    {
        if ($this->items === []) {
            return true;
        }

        $this->capturePreviousNewLines();
        $this->state = 'submit';

        static::output()->write($this->renderTheme());

        return true;
    }

    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
}
