<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'cuisine_type',
        'price_range',
        'opening_hours',
        'description'
    ];
}