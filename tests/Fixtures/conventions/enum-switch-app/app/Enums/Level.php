<?php

namespace App\Enums;

enum Level: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';
    case CRITICAL = 'critical';

    public function describe(string $flag): string
    {
        switch ($flag) {
            case Active:
                return 'active';

            default:
                return 'inactive';
        }
    }
}
