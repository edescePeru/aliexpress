<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillPurchaseContext extends Migration
{
    public function up()
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Esta migración finaliza NOT NULL con sintaxis MySQL/MariaDB.');
        }
        if (!DB::table('companies')->where('id', 1)->where('tenant_id', 1)->exists()) {
            throw new RuntimeException('La Company 1 debe pertenecer al tenant 1 antes del backfill histórico autorizado.');
        }
        DB::transaction(function () {
            // Incluye registros eliminados lógicamente; no se usan modelos ni scopes.
            DB::table('order_purchases')->orderBy('id')->chunkById(300, function ($rows) {
                foreach ($rows as $row) {
                    $tenant = $row->tenant_id ?: 1; $company = $row->company_id ?: 1;
                    if (!DB::table('companies')->where('id', $company)->where('tenant_id', $tenant)->exists()) {
                        throw new RuntimeException('Contexto incoherente en orden #' . $row->id);
                    }
                    DB::table('order_purchases')->where('id', $row->id)->update(['tenant_id' => $tenant, 'company_id' => $company]);
                }
            });
            foreach (['order_purchase_details' => ['order_purchases', 'order_purchase_id'],
                         'material_orders' => ['order_purchase_details', 'order_purchase_detail_id']] as $table => $link) {
                DB::table($table)->orderBy('id')->chunkById(300, function ($rows) use ($table, $link) {
                    foreach ($rows as $row) {
                        $parentId = $row->{$link[1]};
                        $parent = $parentId ? DB::table($link[0])->where('id', $parentId)->first() : null;
                        if (!$parent && ($table !== 'material_orders' || $parentId)) {
                            throw new RuntimeException('Registro sin padre verificable: ' . $table . ' #' . $row->id);
                        }
                        $tenant = $parent ? $parent->tenant_id : 1; $company = $parent ? $parent->company_id : 1;
                        if (($row->tenant_id && (int) $row->tenant_id !== (int) $tenant)
                            || ($row->company_id && (int) $row->company_id !== (int) $company)) {
                            throw new RuntimeException('Contexto contradictorio: ' . $table . ' #' . $row->id);
                        }
                        DB::table($table)->where('id', $row->id)->update(['tenant_id' => $tenant, 'company_id' => $company]);
                    }
                });
            }
        });
        foreach (['order_purchases', 'order_purchase_details', 'material_orders'] as $table) {
            // Nombres constantes del código, nunca datos del usuario. No requiere Doctrine DBAL.
            $this->nullable($table, false);
        }
    }
    public function down()
    {
        foreach (['material_orders', 'order_purchase_details', 'order_purchases'] as $table) {
            $this->nullable($table, true);
        }
        // No borrar atribuciones históricas al revertir solamente esta migración.
    }
    private function nullable($table, $nullable)
    {
        $null = $nullable ? 'NULL' : 'NOT NULL';
        if (!in_array($table, ['order_purchases', 'order_purchase_details', 'material_orders'], true)) {
            throw new RuntimeException('Tabla no permitida para esta migración.');
        }
        $references = ['tenant_id' => 'tenants', 'company_id' => 'companies'];
        $failure = null;
        try {
            // Sentencias separadas: no reutilizar el nombre de una FK dentro
            // del mismo ALTER que la elimina. Comprobar existencia permite reintentar.
            foreach ($references as $column => $parent) {
                $name = $table . '_' . $column . '_foreign';
                if ($this->constraintExists($table, $name)) {
                    DB::statement('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $name . '`');
                }
            }
            DB::statement('ALTER TABLE `' . $table . '` '
                . 'MODIFY `tenant_id` BIGINT UNSIGNED ' . $null . ', '
                . 'MODIFY `company_id` BIGINT UNSIGNED ' . $null);
        } catch (\Throwable $e) {
            $failure = $e;
        }
        // Intentar restaurar ambas FKs incluso si falla el cambio de nulabilidad.
        $restoreErrors = [];
        foreach ($references as $column => $parent) {
            $name = $table . '_' . $column . '_foreign';
            try {
                if (!$this->constraintExists($table, $name)) {
                    DB::statement('ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $name
                        . '` FOREIGN KEY (`' . $column . '`) REFERENCES `' . $parent . '` (`id`) ON DELETE RESTRICT');
                }
            } catch (\Throwable $e) {
                $restoreErrors[] = $name . ': ' . $e->getMessage();
                if (!$failure) { $failure = $e; }
            }
        }
        if ($restoreErrors) {
            throw new RuntimeException('No se pudieron restaurar estas claves; mantenga detenidas las escrituras y revise el error antes de reintentar: '
                . implode('; ', $restoreErrors), 0, $failure);
        }
        if ($failure) { throw $failure; }
    }

    private function constraintExists($table, $name): bool
    {
        return count(DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS '
                . 'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                . "AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = ?",
                [$table, $name]
            )) > 0;
    }
}
