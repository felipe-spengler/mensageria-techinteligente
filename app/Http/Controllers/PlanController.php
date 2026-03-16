<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = \App\Models\Plan::where('is_active', true)->get();
        return view('plans', compact('plans'));
    }

    public function purchase($id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        return view('purchase', compact('plan'));
    }
}
