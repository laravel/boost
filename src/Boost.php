<?php

declare(strict_types=1);

namespace Laravel\Boost;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerCodeEnvironment(string $key, string $className)
 * @method static array getCodeEnvironments()
 *
 * @see \Laravel\Boost\BoostManager
 */
class Boost extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BoostManager::class;
    }
}
