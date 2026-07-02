<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    #[Scope]
    protected function unpaid(Builder $query): Builder
    {
        return $query->whereNull('paid_at');
    }
}
