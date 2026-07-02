<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
}
