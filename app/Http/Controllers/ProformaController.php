<?php
namespace App\Http\Controllers;

// PAY-01: rutas conservadas como retirada explícita; sin leer ni mutar documentos legacy.
class ProformaController extends RetiredCommercialController
{
    public function index() { return $this->unavailable(); }
    public function getDataProformas() { return $this->unavailable(); }
    public function create() { return $this->unavailable(); }
    public function getDataEquipments() { return $this->unavailable(); }
    public function getDataEquipmentDefault() { return $this->unavailable(); }
    public function addDataDefaultEquipmentProforma() { return $this->unavailable(); }
    public function editEquipmentProforma() { return $this->unavailable(); }
    public function updateEquipmentProforma() { return $this->unavailable(); }
    public function destroyEquipmentProforma() { return $this->unavailable(); }
    public function changePercentagesEquipment() { return $this->unavailable(); }
    public function store() { return $this->unavailable(); }
    public function show() { return $this->unavailable(); }
    public function edit() { return $this->unavailable(); }
    public function update() { return $this->unavailable(); }
    public function destroy() { return $this->unavailable(); }
    public function printProformaToCustomer() { return $this->unavailable(); }
    public function vistoBuenoProforma() { return $this->unavailable(); }
}
