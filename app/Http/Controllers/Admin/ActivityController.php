<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;

class ActivityController extends Controller
{
    public function index()
    {
        $ctx = session('ctx', 'All');
        $items = Activity::when($ctx !== 'All', fn ($q) => $q->whereIn('context', [$ctx, 'All']))->latest()->paginate(30);

        return view('admin.activity', compact('items', 'ctx'));
    }
}
