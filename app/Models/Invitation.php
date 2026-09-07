<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_number',
        'guest_code',
        'qr_token',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    protected $appends = [
        'is_checked_in',
        'qr_value',
    ];

    public function event()
    {
        return $this->belongsTo(
            Event::class
        );
    }

    public function checkedInBy()
    {
        return $this->belongsTo(
            User::class,
            'checked_in_by'
        );
    }

    public function getIsCheckedInAttribute(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function getQrValueAttribute(): string
    {
        return $this->event->qr_prefix
            . ':'
            . $this->qr_token;
    }

    public function getQrImageUrl(
        int $size = 300
    ): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?'
            . http_build_query([
                'size' =>
                $size . 'x' . $size,

                'data' =>
                $this->qr_value,

                'format' =>
                'png',

                'margin' =>
                10,
            ]);
    }
}
