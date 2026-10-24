<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddInTransitToItemState extends Migration
{
    public function up()
    {
        $this->assertDriver();
        $column = DB::selectOne("SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'items' AND COLUMN_NAME = 'state_item'");
        $old = "enum('entered','scraped','reserved','exited')";
        $new = "enum('entered','scraped','reserved','exited','in_transit')";
        if (!$column || !in_array($column->column_type, [$old, $new], true)
            || $column->is_nullable !== 'YES' || !in_array($column->column_default, [null, 'NULL'], true)) {
            throw new RuntimeException('La definición de items.state_item no coincide con la esperada. Revisar antes de modificarla.');
        }
        if ($column->column_type === $new) { return; }
        DB::statement("ALTER TABLE `items` MODIFY `state_item` ENUM('entered','scraped','reserved','exited','in_transit') NULL DEFAULT NULL");
    }

    public function down()
    {
        $this->assertDriver();
        // También protege operaciones de cantidades, aunque no existan Items unitarios.
        if (DB::table('items')->where('state_item', 'in_transit')->exists()
            || DB::table('warehouse_transfers')->whereIn('status', ['dispatched', 'partially_received'])->exists()) {
            throw new RuntimeException('No se puede retirar el estado de tránsito con traslados pendientes de recepción.');
        }
        DB::statement("ALTER TABLE `items` MODIFY `state_item` ENUM('entered','scraped','reserved','exited') NULL DEFAULT NULL");
    }

    private function assertDriver(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Esta migración de ENUM está preparada para MySQL/MariaDB.');
        }
    }
}
