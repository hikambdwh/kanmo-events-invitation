<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'type',
        'status',
        'total_items',
        'processed_items',
        'max_guest_number',
        'last_guest_number',
        'file_name',
        'file_path',
        'meta',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'completed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressAttribute(): int
    {
        if ($this->total_items <= 0) {
            return 0;
        }

        return min(
            100,
            (int) round(
                ($this->processed_items / $this->total_items) * 100
            )
        );
    }
}
