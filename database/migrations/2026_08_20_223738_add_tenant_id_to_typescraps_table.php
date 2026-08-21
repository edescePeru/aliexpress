<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTenantIdToTypescrapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('typescraps', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->index(
                'tenant_id',
                'typescraps_tenant_id_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('typescraps', function (Blueprint $table) {
            $table->dropIndex(
                'typescraps_tenant_id_index'
            );

            $table->dropColumn(
                'tenant_id'
            );
        });
    }
}
