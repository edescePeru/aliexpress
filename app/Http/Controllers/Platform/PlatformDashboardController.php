<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PlatformDashboardController extends Controller
{
    public function index()
    {
        $summary = app(\App\Services\PlatformDashboardSummary::class)->data();
        $user = Auth::user();

        return view(
            'platform.dashboard.index',
            array_merge(['user' => $user], $summary)
        );
    }
}