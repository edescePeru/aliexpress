<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RetiredReferralGuideController extends Controller
{
    public function unavailable(Request $request)
    {
        $url = route('shipping_guides.view');
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Esta sección fue reemplazada por las guías electrónicas.', 'url' => $url], 410);
        }
        return response()->view('referralGuide.retired', ['url' => $url], 410);
    }
}
