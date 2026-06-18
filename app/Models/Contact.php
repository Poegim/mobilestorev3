<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    
    protected $fillable = [
        'name', 'identity_number', 'email', 'phone',
        'city', 'postal_code', 'street', 'notes',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}