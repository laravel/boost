<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
