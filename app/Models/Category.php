<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
  protected $fillable = ['name', 'sizes', 'user_id'];

    // კატეგორიას აქვს ბევრი (hasMany) ზომა
    // public function sizes()
    // {
    //     return $this->hasMany(Size::class);
    // }
}
