<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereNull('paid_at');
    }
}
