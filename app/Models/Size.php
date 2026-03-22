<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    // ამ ველების მასიურად შევსება (Mass Assignment) დაშვებულია
    protected $fillable = ['category_id', 'name'];

    // კავშირი კატეგორიასთან: ზომა ეკუთვნის კატეგორიას
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}