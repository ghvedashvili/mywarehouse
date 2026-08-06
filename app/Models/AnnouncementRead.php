<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['announcement_id', 'user_id'];
}
