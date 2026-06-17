<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'percentage'];

    public function getReal(): float
    {
        return $this->percentage / 100;
    }
}