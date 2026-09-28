<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddCodeDefaultActiveToTypeTaxesTable extends Migration
{
    public function up()
    {
        Schema::table('type_taxes', function (Blueprint $table) {

            $table->string('code', 50)
                ->nullable()
                ->after('id');

            $table->boolean('is_default')
                ->default(false)
                ->after('tax');

            $table->boolean('is_active')
                ->default(true)
                ->after('is_default');

            $table->unique('code');
            $table->index('is_default');
            $table->index('is_active');
        });

        /*
         * Backfill del registro histórico actual.
         *
         * Según tu tabla actual, el registro existente
         * corresponde al IGV 18%.
         */
        $igv = DB::table('type_taxes')
            ->orderBy('id')
            ->first();

        if ($igv) {
            DB::table('type_taxes')
                ->where('id', $igv->id)
                ->update([
                    'code' => 'IGV_18',
                    'is_default' => true,
                    'is_active' => true,
                ]);
        }

        /*
         * Validar que ningún registro haya quedado sin code.
         */
        $withoutCode = DB::table('type_taxes')
            ->whereNull('code')
            ->count();

        if ($withoutCode > 0) {
            throw new \RuntimeException(
                'Existen tipos de impuesto sin código después del backfill.'
            );
        }

        /*
         * Luego del backfill hacemos code NOT NULL.
         */
        DB::statement(
            'ALTER TABLE type_taxes
             MODIFY code VARCHAR(50) NOT NULL'
        );
    }

    public function down()
    {
        Schema::table('type_taxes', function (Blueprint $table) {

            $table->dropUnique([
                'code',
            ]);

            $table->dropIndex([
                'is_default',
            ]);

            $table->dropIndex([
                'is_active',
            ]);

            $table->dropColumn([
                'code',
                'is_default',
                'is_active',
            ]);
        });
    }
}
