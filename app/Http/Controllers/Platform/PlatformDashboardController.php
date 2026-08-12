<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PlatformDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view(
            'platform.dashboard.index',
            compact('user')
        );
    }
}