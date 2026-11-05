<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class MaterialPresentationBranch extends Model
{
    use BelongsToTenant;
    protected $table = 'material_presentation_branches';
    protected $fillable = ['tenant_id','company_id','branch_id','material_presentation_id','price','currency','is_active'];
    protected $casts = ['price'=>'decimal:2','is_active'=>'boolean'];
    public function presentation() { return $this->belongsTo(MaterialPresentation::class, 'material_presentation_id'); }
}
