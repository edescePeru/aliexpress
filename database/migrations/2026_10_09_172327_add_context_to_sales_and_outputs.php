<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContextToSalesAndOutputs extends Migration
{
    private function tables()
    {
        return [
            'sales' => true,
            'sale_details' => false,
            'sale_partial_payments' => true,
            'outputs' => true,
            'output_details' => false,
        ];
    }

    public function up()
    {
        // Preflight antes de modificar cualquiera de las cinco tablas.
        foreach ($this->tables() as $name => $hasBranch) {
            if (!Schema::hasTable($name)) {
                throw new \RuntimeException('No existe la tabla ' . $name);
            }
            $columns = ['tenant_id', 'company_id'];
            if ($hasBranch) {
                $columns[] = 'branch_id';
            }
            foreach ($columns as $column) {
                if (Schema::hasColumn($name, $column)) {
                    throw new \RuntimeException(
                        'Ya existe ' . $name . '.' . $column . '. Revisar antes de continuar.'
                    );
                }
            }
        }

        foreach ($this->tables() as $name => $hasBranch) {
            Schema::table($name, function (Blueprint $table) use ($name, $hasBranch) {
                // Nullable durante la adaptación de todos los escritores.
                // Sin default: el contexto de nuevos registros debe ser explícito.
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                if ($hasBranch) {
                    $table->unsignedBigInteger('branch_id')->nullable();
                }

                $table->index(['tenant_id', 'company_id'], $name . '_tenant_company_idx');
                $table->index('company_id', $name . '_company_idx');
                if ($hasBranch) {
                    $table->index(['company_id', 'branch_id'], $name . '_company_branch_idx');
                    $table->index('branch_id', $name . '_branch_idx');
                }

                // Evitar borrado automático de operaciones históricas.
                $table->foreign('tenant_id', $name . '_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('restrict');
                $table->foreign('company_id', $name . '_company_fk')
                    ->references('id')->on('companies')->onDelete('restrict');
                if ($hasBranch) {
                    $table->foreign('branch_id', $name . '_branch_fk')
                        ->references('id')->on('branches')->onDelete('restrict');
                }
            });
        }
    }

    public function down()
    {
        foreach (array_reverse($this->tables(), true) as $name => $hasBranch) {
            Schema::table($name, function (Blueprint $table) use ($name, $hasBranch) {
                $table->dropForeign($name . '_tenant_fk');
                $table->dropForeign($name . '_company_fk');
                $table->dropIndex($name . '_tenant_company_idx');
                $table->dropIndex($name . '_company_idx');
                $columns = ['tenant_id', 'company_id'];
                if ($hasBranch) {
                    $table->dropForeign($name . '_branch_fk');
                    $table->dropIndex($name . '_company_branch_idx');
                    $table->dropIndex($name . '_branch_idx');
                    $columns[] = 'branch_id';
                }
                $table->dropColumn($columns);
            });
        }
    }
}
