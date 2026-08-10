<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['message', 'created_by', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    // ყველაზე ახალი active announcement — თუ user-ს არ წაუკითხავს
    public static function latestUnreadFor(int $userId): ?self
    {
        $latest = static::where('is_active', true)->latest()->first();
        if (!$latest) return null;

        $hasRead = \App\Models\AnnouncementRead::where('announcement_id', $latest->id)
            ->where('user_id', $userId)
            ->exists();

        return $hasRead ? null : $latest;
    }
}
