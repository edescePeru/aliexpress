<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillPaymentDeadlineContext extends Migration
{
    // Contexto histórico confirmado por el usuario para documentos sin contexto.
    const LEGACY_TENANT_ID = 1;
    const LEGACY_COMPANY_ID = 1;

    public function up()
    {
        if (DB::connection()->getDriverName() !== 'mysql') { throw new RuntimeException('Esta migración de estructura final requiere MySQL/MariaDB.'); }
        $this->backfill();
        DB::statement('ALTER TABLE payment_deadlines MODIFY tenant_id BIGINT UNSIGNED NOT NULL, MODIFY company_id BIGINT UNSIGNED NOT NULL');
        foreach (['tenant_id' => 'tenants', 'company_id' => 'companies'] as $column => $parent) {
            $name = 'pd_'.$column.'_fk';
            $exists = DB::select('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?', ['payment_deadlines', $name]);
            if (!$exists) {
                Schema::table('payment_deadlines', function (Blueprint $table) use ($column, $parent, $name) {
                    $table->foreign($column, $name)->references('id')->on($parent)->onDelete('restrict');
                });
            }
        }
    }

    public function backfill(): void
    {
        DB::transaction(function () {
            $deadlines = DB::table('payment_deadlines')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $refs = []; $owners = [];
            foreach ($this->referenceTables() as $table) {
                $columns = Schema::getColumnListing($table);
                if (!in_array('id', $columns, true)) { throw new RuntimeException('Referencia sin identificador simple: '.$table); }
                $select = array_values(array_intersect(['id', 'payment_deadline_id', 'tenant_id', 'company_id', 'entry_id', 'order_purchase_id', 'order_service_id', 'order_purchase_finance_id'], $columns));
                foreach (DB::table($table)->select($select)->whereNotNull('payment_deadline_id')->orderBy('id')->lockForUpdate()->get() as $row) {
                    if (!$deadlines->has($row->payment_deadline_id)) { throw new RuntimeException('Plazo inexistente en '.$table.' #'.$row->id); }
                    $context = $this->owner($table, $row);
                    $key = $context[0].':'.$context[1];
                    $owners[$row->payment_deadline_id][$key] = $context;
                    $refs[] = [$table, $row->id, $row->payment_deadline_id, $key];
                }
            }
            $plan = [];
            foreach ($deadlines as $id => $row) {
                $contexts = $owners[$id] ?? [];
                $originalOwner = null;
                if ($row->tenant_id !== null || $row->company_id !== null) {
                    $originalOwner = $this->owner('payment_deadlines', $row);
                    $contexts[$originalOwner[0].':'.$originalOwner[1]] = $originalOwner;
                }
                if (!$contexts) {
                    $originalOwner = $this->legacy('payment_deadlines #'.$id);
                    $contexts[$originalOwner[0].':'.$originalOwner[1]] = $originalOwner;
                }
                ksort($contexts);
                if (!$originalOwner) { $originalOwner = $contexts['1:1'] ?? reset($contexts); }
                $plan[$id] = [$row, $originalOwner, $contexts];
            }
            // El plan completo se valida antes de cambiar identificadores o crear copias.
            $mapping = [];
            foreach ($plan as $id => $item) {
                [$row, $originalOwner, $contexts] = $item;
                DB::table('payment_deadlines')->where('id', $id)->update(['tenant_id' => $originalOwner[0], 'company_id' => $originalOwner[1]]);
                foreach ($contexts as $key => $context) {
                    if ($context === $originalOwner) { $mapping[$id][$key] = $id; continue; }
                    $copy = (array) $row; unset($copy['id']);
                    $copy['tenant_id'] = $context[0]; $copy['company_id'] = $context[1];
                    $mapping[$id][$key] = DB::table('payment_deadlines')->insertGetId($copy);
                }
            }
            foreach ($refs as $ref) {
                [$table, $id, $source, $key] = $ref;
                $target = $mapping[$source][$key];
                if ((int) $source !== (int) $target) { DB::table($table)->where('id', $id)->update(['payment_deadline_id' => $target]); }
            }
        });
    }

    private function owner(string $table, $row, array $trail = []): array
    {
        $identity = $table.':'.$row->id;
        if (isset($trail[$identity])) { throw new RuntimeException('Referencias circulares: '.$identity); }
        $trail[$identity] = true; $contexts = [];
        if (isset($row->company_id)) {
            $company = DB::table('companies')->where('id', $row->company_id)->first();
            if (!$company || !$company->tenant_id || (isset($row->tenant_id) && (int) $row->tenant_id !== (int) $company->tenant_id)) {
                throw new RuntimeException('Empresa/tenant incoherentes en '.$identity);
            }
            $contexts[] = [(int) $company->tenant_id, (int) $company->id];
        }
        if ($table === 'supplier_credits') {
            foreach (['entry_id' => 'entries', 'order_purchase_id' => 'order_purchases', 'order_service_id' => 'order_services', 'order_purchase_finance_id' => 'order_purchase_finances'] as $field => $parentTable) {
                if (empty($row->$field)) { continue; }
                if (!Schema::hasTable($parentTable) || !($parent = DB::table($parentTable)->where('id', $row->$field)->first())) {
                    throw new RuntimeException('Documento padre inexistente en '.$identity);
                }
                $contexts[] = $this->owner($parentTable, $parent, $trail);
            }
        }
        if (!$contexts) { $contexts[] = $this->legacy($identity); }
        $result = $contexts[0];
        foreach ($contexts as $context) {
            if ($context !== $result) { throw new RuntimeException('Documentos de empresas diferentes en '.$identity); }
        }
        if (isset($row->tenant_id) && (int) $row->tenant_id !== $result[0]) { throw new RuntimeException('Tenant contradictorio en '.$identity); }
        return $result;
    }

    private function legacy(string $identity): array
    {
        if (!static::LEGACY_TENANT_ID || !static::LEGACY_COMPANY_ID) {
            throw new RuntimeException('Falta identificar el tenant/empresa histórico de '.$identity.'. Configure LEGACY_TENANT_ID y LEGACY_COMPANY_ID solo después de confirmarlo.');
        }
        $company = DB::table('companies')->where('id', static::LEGACY_COMPANY_ID)->where('tenant_id', static::LEGACY_TENANT_ID)->first();
        if (!$company) { throw new RuntimeException('El contexto histórico configurado no es válido.'); }
        return [(int) static::LEGACY_TENANT_ID, (int) static::LEGACY_COMPANY_ID];
    }

    private function referenceTables(): array
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return array_map(function ($row) { return $row->name; }, DB::select(
                "SELECT c.TABLE_NAME AS name FROM information_schema.COLUMNS c JOIN information_schema.TABLES t ON c.TABLE_SCHEMA=t.TABLE_SCHEMA AND c.TABLE_NAME=t.TABLE_NAME WHERE c.TABLE_SCHEMA=DATABASE() AND c.COLUMN_NAME=? AND t.TABLE_TYPE='BASE TABLE'", ['payment_deadline_id']));
        }
        $tables = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type='table'") as $row) {
            if (Schema::hasColumn($row->name, 'payment_deadline_id')) { $tables[] = $row->name; }
        }
        return $tables;
    }

    public function down() { throw new RuntimeException('El backfill reasigna referencias históricas; su reversión requiere restaurar el respaldo previo.'); }
}
