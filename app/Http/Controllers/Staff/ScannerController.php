<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'qr_prefix',
            ]);

        return view(
            'staff.scanner',
            compact('events')
        );
    }
}
