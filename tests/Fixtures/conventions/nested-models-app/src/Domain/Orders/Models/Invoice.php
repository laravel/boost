<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['reference', 'amount'];
}
