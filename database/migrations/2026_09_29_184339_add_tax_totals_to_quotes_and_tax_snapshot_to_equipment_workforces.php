<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxTotalsToQuotesAndTaxSnapshotToEquipmentWorkforces extends Migration
{
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {

            $table->decimal(
                'exonerada',
                15,
                10
            )
                ->default(0)
                ->after('gravada');

            $table->decimal(
                'inafecta',
                15,
                10
            )
                ->default(0)
                ->after('exonerada');
        });

        Schema::table(
            'equipment_workforces',
            function (Blueprint $table) {

                $table
                    ->unsignedBigInteger(
                        'type_tax_id'
                    )
                    ->nullable()
                    ->after('equipment_id');

                $table
                    ->decimal(
                        'tax_rate',
                        8,
                        4
                    )
                    ->nullable()
                    ->after('type_tax_id');

                $table
                    ->foreign(
                        'type_tax_id'
                    )
                    ->references('id')
                    ->on('type_taxes');

                $table->index(
                    'type_tax_id'
                );
            }
        );
    }

    public function down()
    {
        Schema::table(
            'equipment_workforces',
            function (Blueprint $table) {

                $table->dropForeign([
                    'type_tax_id',
                ]);

                $table->dropIndex([
                    'type_tax_id',
                ]);

                $table->dropColumn([
                    'type_tax_id',
                    'tax_rate',
                ]);
            }
        );

        Schema::table('quotes', function (Blueprint $table) {

            $table->dropColumn([
                'exonerada',
                'inafecta',
            ]);
        });
    }
}
