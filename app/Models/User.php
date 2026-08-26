<?php


namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
	use Notifiable;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name', 'email', 'password', 'role', 'chat_enabled', 'customer_id', 'customer_linked_from'];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token',
	];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function stickyNotes()
    {
        return $this->hasMany(\App\Models\StickyNote::class);
    }
}
