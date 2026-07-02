<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }
}
