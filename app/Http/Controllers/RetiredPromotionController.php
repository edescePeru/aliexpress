<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class RetiredPromotionController extends Controller
{
    public function unavailable(Request $request)
    {
        $message = 'Las promociones legacy por límite, temporada y su orden de aplicación fueron retiradas. Los datos históricos se conservan.';
        if ($request->expectsJson()) { return response()->json(['message'=>$message],410); }
        return response()->view('promotions.retired',['message'=>$message],410);
    }
}
