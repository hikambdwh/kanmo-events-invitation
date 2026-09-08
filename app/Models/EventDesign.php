<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventDesign extends Model
{
    protected $fillable = [
        'event_id',
        'background_path',
        'canvas_width',
        'canvas_height',
        'design_data',
    ];

    protected $casts = [
        'design_data' => 'array',
    ];

    protected $table = 'event_designs';

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
