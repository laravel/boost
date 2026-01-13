<?php

declare(strict_types=1);

namespace Laravel\Boost\Prompts;

use Illuminate\Support\Collection;
use Laravel\Boost\Prompts\Themes\Default\GridRenderer;
use Laravel\Prompts\Prompt;

class Grid extends Prompt
{
    /**
     * @var array<int, string>
     */
    public array $items;

    public int $maxWidth;

    /***
     * @param  array<int, string>|Collection<int, string>  $items
     */
    public function __construct(array|Collection $items = [], ?int $maxWidth = null)
    {
        $this->items = $items instanceof Collection ? $items->all() : $items;
        $this->maxWidth = $maxWidth ?? static::terminal()->cols() ?: 80;
    }

    public static function register(): void
    {
        static::$themes['default'][self::class] = GridRenderer::class;
    }

    public function display(): void
    {
        $this->prompt();
    }

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

    public function value(): bool
    {
        return true;
    }
}
