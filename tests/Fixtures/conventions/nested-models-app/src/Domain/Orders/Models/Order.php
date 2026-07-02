<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['reference', 'amount'];
}
