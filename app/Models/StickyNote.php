<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StickyNote extends Model
{
    protected $fillable = ['user_id', 'content', 'pos_x', 'pos_y'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
