<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeInvoiceCategoriesToCompany extends Migration
{
    public function up()
    {
        Schema::table('category_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->index(['tenant_id', 'company_id', 'deleted_at'], 'invoice_categories_company_index');
        });

        DB::transaction(function () {
            $references = DB::table('entries as e')->join('companies as c', function ($join) {
                $join->on('c.id', '=', 'e.company_id')->on('c.tenant_id', '=', 'e.tenant_id');
            })->whereNotNull('e.category_invoice_id')
                ->select('e.category_invoice_id', 'e.tenant_id', 'e.company_id')->distinct()->get();
            $contexts = $references->unique('company_id')->values();
            $byCategory = $references->groupBy('category_invoice_id');

            DB::table('category_invoices')->whereNull('company_id')->orderBy('id')->chunkById(200, function ($categories) use ($byCategory, $contexts) {
                foreach ($categories as $category) {
                    $owners = $byCategory->get($category->id, collect())->sortBy('company_id')->values();
                    if ($owners->isEmpty() && $contexts->count() === 1
                        && !DB::table('entries')->where('category_invoice_id', $category->id)->exists()) {
                        $owners = $contexts;
                    }
                    foreach ($owners as $index => $owner) {
                        $context = ['tenant_id' => $owner->tenant_id, 'company_id' => $owner->company_id];
                        if ($index === 0) {
                            DB::table('category_invoices')->where('id', $category->id)->update($context);
                            continue;
                        }
                        $copy = (array) $category;
                        unset($copy['id']);
                        $copyId = DB::table('category_invoices')->insertGetId(array_replace($copy, $context));
                        DB::table('entries')->where('category_invoice_id', $category->id)
                            ->where($context)->update(['category_invoice_id' => $copyId]);
                    }
                }
            });
        });
    }

    public function down()
    {
        throw new \RuntimeException('Esta migración reasigna referencias históricas. Restaura el respaldo para revertirla.');
    }
}
