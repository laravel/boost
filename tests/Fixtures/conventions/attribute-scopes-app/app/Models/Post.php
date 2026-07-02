<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
