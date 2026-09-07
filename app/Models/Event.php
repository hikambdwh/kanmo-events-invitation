<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'qr_prefix',
        'event_date',
        'venue',
        'address',
    ];

    protected $casts = [
        'event_date' => 'datetime',
    ];

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }
}
