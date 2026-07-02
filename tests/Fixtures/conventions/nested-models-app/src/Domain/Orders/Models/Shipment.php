<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = ['reference', 'amount'];
}
