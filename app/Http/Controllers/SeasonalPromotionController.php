<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class SeasonalPromotionController extends RetiredPromotionController
{
    public function index() { return $this->unavailable(request()); }
    public function getDataPromotions(Request $request, $pageNumber = 1) { return $this->unavailable(request()); }
    public function create() { return $this->unavailable(request()); }
    public function store(Request $request) { return $this->unavailable(request()); }
    public function destroy(Request $request) { return $this->unavailable(request()); }
}
