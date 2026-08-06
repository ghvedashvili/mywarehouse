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

    // ბოლო active announcement, რომელიც user-ს არ წაუკითხავს
    public static function latestUnreadFor(int $userId): ?self
    {
        return static::where('is_active', true)
            ->whereNotExists(function ($q) use ($userId) {
                $q->from('announcement_reads')
                  ->whereColumn('announcement_reads.announcement_id', 'announcements.id')
                  ->where('announcement_reads.user_id', $userId);
            })
            ->latest()
            ->first();
    }
}
