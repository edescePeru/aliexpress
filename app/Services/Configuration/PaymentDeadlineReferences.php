<?php
namespace App\Services\Configuration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentDeadlineReferences
{
    public static function tables(): array
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return array_map(function ($row) { return $row->name; }, DB::select(
                "SELECT c.TABLE_NAME AS name FROM information_schema.COLUMNS c JOIN information_schema.TABLES t ON c.TABLE_SCHEMA=t.TABLE_SCHEMA AND c.TABLE_NAME=t.TABLE_NAME WHERE c.TABLE_SCHEMA=DATABASE() AND c.COLUMN_NAME=? AND t.TABLE_TYPE='BASE TABLE'", ['payment_deadline_id']
            ));
        }
        // Para verificaciones aisladas con SQLite.
        $tables = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table'") as $row) {
            if (Schema::hasColumn($row->name, 'payment_deadline_id')) { $tables[] = $row->name; }
        }
        return $tables;
    }

    public static function used(int $id): bool
    {
        foreach (self::tables() as $table) {
            // Incluye históricos y borrados lógicos: no usar scopes de otros modelos.
            if (DB::table($table)->where('payment_deadline_id', $id)->exists()) { return true; }
        }
        return false;
    }
}
