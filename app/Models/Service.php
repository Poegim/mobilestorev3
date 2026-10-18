<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'tax_id', 'price', 'internal_cost'];

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }
}