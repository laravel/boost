<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

enum Currency: string
{
    case Usd = 'USD';
    case Eur = 'EUR';
}
