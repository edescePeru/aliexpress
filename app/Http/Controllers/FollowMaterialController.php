<?php
namespace App\Http\Controllers;

// Compatibilidad de llamadas antiguas: sin acceso a datos, exportación o correo.
class FollowMaterialController extends RetiredFollowMaterialController
{
    public function getFollowMaterial($material_id) { return $this->unavailable(request()); }
    public function followMaterial($material_id) { return $this->unavailable(request()); }
    public function unfollowMaterial($material_id) { return $this->unavailable(request()); }
    public function index() { return $this->unavailable(request()); }
    public function getJsonFollowMaterials() { return $this->unavailable(request()); }
    public function unFollowMaterialUser($follow_id) { return $this->unavailable(request()); }
    public function getJsonDetailFollowMaterial($id_material) { return $this->unavailable(request()); }
    public function getJsonStockAllMaterials() { return $this->unavailable(request()); }
    public function indexStock() { return $this->unavailable(request()); }
    public function sendEmailWithExcel() { return $this->unavailable(request()); }
}
