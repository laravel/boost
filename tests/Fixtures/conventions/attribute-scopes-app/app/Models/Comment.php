<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }
}
