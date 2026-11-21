<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class RetiredFollowMaterialController extends Controller
{
    public function unavailable(Request $request)
    {
        $message = 'El seguimiento y las alertas antiguas de materiales fueron retirados. Sus datos históricos se conservan.';
        if ($request->expectsJson()) { return response()->json(['message'=>$message], 410); }
        return response()->view('follow.retired', ['message'=>$message], 410);
    }
}
