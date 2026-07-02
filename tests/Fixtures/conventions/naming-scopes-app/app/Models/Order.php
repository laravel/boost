<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
