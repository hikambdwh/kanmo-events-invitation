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

    public function activeDesign()
    {
        return $this->hasOne(EventDesign::class)
            ->where('is_active', true);
    }

    public function exports()
    {
        return $this->hasMany(Export::class);
    }

    public function design()
    {
        return $this->hasOne(EventDesign::class);
    }
}
