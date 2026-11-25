<?php
namespace App\Http\Controllers;

// Retiro explícito autorizado: rutas históricas sin consultas, mutaciones, PDF ni proveedor.
class OrderPurchaseFinanceController extends Controller
{
    private function unavailable()
    {
        $message = 'Órdenes de Compra Finanzas fue retirado. Sus datos históricos se conservan. Utilice el módulo vigente de Órdenes de compra.';
        if (request()->expectsJson()) { return response()->json(['message' => $message], 410); }
        return response()->view('orderPurchaseFinance.retired', ['message' => $message], 410);
    }
    public function indexOrderPurchaseFinance() { return $this->unavailable(); }
    public function createOrderPurchaseFinance() { return $this->unavailable(); }
    public function storeOrderPurchaseFinance() { return $this->unavailable(); }
    public function getAllOrderFinance() { return $this->unavailable(); }
    public function editOrderPurchaseFinance() { return $this->unavailable(); }
    public function updateOrderPurchaseFinance() { return $this->unavailable(); }
    public function destroyFinanceDetail() { return $this->unavailable(); }
    public function updateFinanceDetail() { return $this->unavailable(); }
    public function showOrderPurchaseFinance() { return $this->unavailable(); }
    public function destroyOrderPurchaseFinance() { return $this->unavailable(); }
    public function changeStatusOrderPurchaseFinance() { return $this->unavailable(); }
    public function indexOrderPurchaseFinanceDelete() { return $this->unavailable(); }
    public function getOrderDeleteFinance() { return $this->unavailable(); }
    public function showOrderPurchaseFinanceDelete() { return $this->unavailable(); }
    public function printOrderPurchaseFinanceDelete() { return $this->unavailable(); }
    public function printOrderPurchaseFinance() { return $this->unavailable(); }
    public function restoreOrderPurchaseFinanceDelete() { return $this->unavailable(); }
    public function indexOrderPurchaseFinanceRegularize() { return $this->unavailable(); }
    public function getAllOrderRegularizeFinance() { return $this->unavailable(); }
    public function indexOrderPurchaseFinanceLost() { return $this->unavailable(); }
    public function getAllOrderPurchaseFinanceLost() { return $this->unavailable(); }
    public function regularizeAutoOrderEntryPurchaseFinance() { return $this->unavailable(); }
    public function regularizeEntryToOrderPurchaseFinance() { return $this->unavailable(); }
    public function getTipoDeCambio() { return $this->unavailable(); }
    public function obtenerTipoCambio() { return $this->unavailable(); }
}
