<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = ['reference', 'amount'];
}
