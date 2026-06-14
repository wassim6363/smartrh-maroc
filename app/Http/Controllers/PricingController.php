<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class PricingController extends Controller
{
    public function __invoke()
    {
        return view('pricing', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
