<?php
namespace App\Http\Controllers;

// PAY-01: rutas conservadas como retirada explícita; sin leer ni mutar documentos legacy.
class OrderServiceController extends RetiredCommercialController
{
    public function indexOrderServices() { return $this->unavailable(); }
    public function listOrderServices() { return $this->unavailable(); }
    public function createOrderServices() { return $this->unavailable(); }
    public function storeOrderServices() { return $this->unavailable(); }
    public function showOrderService() { return $this->unavailable(); }
    public function editOrderService() { return $this->unavailable(); }
    public function updateOrderService() { return $this->unavailable(); }
    public function destroyOrderService() { return $this->unavailable(); }
    public function getAllOrderService() { return $this->unavailable(); }
    public function printOrderService() { return $this->unavailable(); }
    public function updateDetail() { return $this->unavailable(); }
    public function destroyDetail() { return $this->unavailable(); }
    public function regularizeOrderService() { return $this->unavailable(); }
    public function regularizePostOrderService() { return $this->unavailable(); }
    public function indexServices() { return $this->unavailable(); }
    public function getAllOrderRegularizeService() { return $this->unavailable(); }
    public function regularizeAutoOrderEntryService() { return $this->unavailable(); }
    public function regularizeEntryToOrderService() { return $this->unavailable(); }
    public function indexOrderServiceRegularize() { return $this->unavailable(); }
    public function getAllOrderRegularize() { return $this->unavailable(); }
    public function indexOrderServiceDeleted() { return $this->unavailable(); }
    public function getAllOrderDeleted() { return $this->unavailable(); }
    public function indexOrderServiceLost() { return $this->unavailable(); }
    public function getAllOrderLost() { return $this->unavailable(); }
    public function onlyZeros() { return $this->unavailable(); }
    public function getTipoDeCambio() { return $this->unavailable(); }
    public function obtenerTipoCambio() { return $this->unavailable(); }
}
