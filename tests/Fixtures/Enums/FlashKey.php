<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

enum FlashKey: string
{
    case Success = 'success';
    case Error = 'error';
}
