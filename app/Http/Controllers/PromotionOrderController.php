<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class PromotionOrderController extends RetiredPromotionController
{
    public function index() { return $this->unavailable(request()); }
    public function getDataPromotions(Request $request, $pageNumber = 1) { return $this->unavailable(request()); }
    public function updateOrder(Request $request) { return $this->unavailable(request()); }
    public function checkPromotions(Request $request) { return $this->unavailable(request()); }
}
