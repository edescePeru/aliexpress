<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};

class AddBranchPresentationOfferings extends Migration
{
    public function up()
    {
        $invalid=DB::table('material_presentations as p')->leftJoin('materials as m','m.id','=','p.material_id')
            ->where(function($q){$q->whereNull('m.id')->orWhereNull('m.tenant_id')->orWhere('m.tenant_id',0);})->exists();
        if($invalid)throw new RuntimeException('Hay presentaciones sin material o tenant identificable. Corrija esas referencias antes de migrar.');
        if(!Schema::hasColumn('material_presentations','tenant_id')){
            Schema::table('material_presentations',function(Blueprint $t){$t->unsignedBigInteger('tenant_id')->nullable()->index('mp_tenant_idx');});
        }
        $mismatch=DB::table('material_presentations as p')->join('materials as m','m.id','=','p.material_id')
            ->whereNotNull('p.tenant_id')->whereColumn('p.tenant_id','<>','m.tenant_id')->exists();
        if($mismatch)throw new RuntimeException('Hay presentaciones con tenant incompatible con su material.');
        DB::statement('UPDATE material_presentations p INNER JOIN materials m ON m.id = p.material_id SET p.tenant_id = m.tenant_id WHERE p.tenant_id IS NULL');
        DB::statement('ALTER TABLE material_presentations MODIFY tenant_id BIGINT UNSIGNED NOT NULL');
        if(!Schema::hasTable('material_presentation_branches')){
            Schema::create('material_presentation_branches',function(Blueprint $t){
                $t->bigIncrements('id');
                $t->unsignedBigInteger('tenant_id');
                $t->unsignedBigInteger('company_id');
                $t->unsignedBigInteger('branch_id');
                $t->unsignedBigInteger('material_presentation_id');
                $t->decimal('price',14,2);
                $t->char('currency',3);
                $t->boolean('is_active')->default(false);
                $t->timestamps();
                $t->unique(['tenant_id','company_id','branch_id','material_presentation_id'],'mp_branch_offer_unique');
                $t->foreign('tenant_id','mpb_tenant_fk')->references('id')->on('tenants')->onDelete('restrict');
                $t->foreign('company_id','mpb_company_fk')->references('id')->on('companies')->onDelete('restrict');
                $t->foreign('branch_id','mpb_branch_fk')->references('id')->on('branches')->onDelete('restrict');
                $t->foreign('material_presentation_id','mpb_definition_fk')->references('id')->on('material_presentations')->onDelete('restrict');
            });
        }
        if(!Schema::hasColumn('sale_details','presentation_label_snapshot')){
            Schema::table('sale_details',function(Blueprint $t){$t->string('presentation_label_snapshot')->nullable();});
        }
        // Do not infer branch ownership or copy the old shared price into every branch.
    }
    public function down()
    {
        Schema::dropIfExists('material_presentation_branches');
        if(Schema::hasColumn('sale_details','presentation_label_snapshot')){
            Schema::table('sale_details',function(Blueprint $t){$t->dropColumn('presentation_label_snapshot');});
        }
        if(Schema::hasColumn('material_presentations','tenant_id')){
            Schema::table('material_presentations',function(Blueprint $t){$t->dropColumn('tenant_id');});
        }
    }
}
