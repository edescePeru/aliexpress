<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};

class AddCashRegularizationPostingIdentity extends Migration
{
    public function up()
    {
        if(!Schema::hasColumn('cash_movements','balance_effect')){
            Schema::table('cash_movements',function(Blueprint $t){$t->boolean('balance_effect')->nullable();});
        }
        if(!Schema::hasColumn('cash_movements','regularization_source_id')){
            Schema::table('cash_movements',function(Blueprint $t){
                $t->unsignedBigInteger('regularization_source_id')->nullable();
                $t->unique('regularization_source_id','cash_regularization_source_unique');
                $t->foreign('regularization_source_id','cash_regularization_source_fk')
                    ->references('id')->on('cash_movements')->onDelete('restrict');
            });
        }
        // Historical rows remain NULL: their prior balance behavior is preserved.
    }
    public function down()
    {
        if((Schema::hasColumn('cash_movements','balance_effect')
            &&DB::table('cash_movements')->where('balance_effect',false)->exists())
            ||(Schema::hasColumn('cash_movements','regularization_source_id')
            &&DB::table('cash_movements')->whereNotNull('regularization_source_id')->exists()))
            throw new RuntimeException('Existen regularizaciones posteriores al cierre. Revertir requiere conciliación para no duplicar importes.');
        if(Schema::hasColumn('cash_movements','regularization_source_id')){
            Schema::table('cash_movements',function(Blueprint $t){
                $t->dropForeign('cash_regularization_source_fk');
                $t->dropUnique('cash_regularization_source_unique');
                $t->dropColumn('regularization_source_id');
            });
        }
        if(Schema::hasColumn('cash_movements','balance_effect')){
            Schema::table('cash_movements',function(Blueprint $t){$t->dropColumn('balance_effect');});
        }
    }
}
