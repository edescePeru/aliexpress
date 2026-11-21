<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class PromotionLimitController extends RetiredPromotionController
{
    public function index() { return $this->unavailable(request()); }
    public function getMaterials(Request $request) { return $this->unavailable(request()); }
    public function getMaterialTotals() { return $this->unavailable(request()); }
    public function getDataPromotions(Request $request, $pageNumber = 1) { return $this->unavailable(request()); }
    public function create() { return $this->unavailable(request()); }
    public function store(Request $request) { return $this->unavailable(request()); }
    public function edit($id) { return $this->unavailable(request()); }
    public function update(Request $request) { return $this->unavailable(request()); }
    public function destroy($promotion_id) { return $this->unavailable(request()); }
}
