<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'parent_category_id'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_category_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'parent_category_id');
    }

    public function isRoot(): bool
    {
        return $this->parent_category_id === 0;
    }
}