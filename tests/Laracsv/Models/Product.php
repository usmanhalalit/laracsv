<?php

namespace Laracsv\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'id', 'updated_at', 'created_at',
    ];

    protected $casts = [
        'price' => 'float',
        'original_price' => 'float',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
