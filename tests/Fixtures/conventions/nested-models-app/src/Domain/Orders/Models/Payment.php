<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['reference', 'amount'];
}
