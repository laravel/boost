<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
