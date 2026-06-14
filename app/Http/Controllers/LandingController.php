<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->take(4)->get(),
        ]);
    }
}
