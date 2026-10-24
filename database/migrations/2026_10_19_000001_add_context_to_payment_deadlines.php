<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContextToPaymentDeadlines extends Migration
{
    public function up()
    {
        Schema::table('payment_deadlines', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->index(['tenant_id', 'company_id'], 'pd_company_context_idx');
        });
    }
    public function down()
    {
        Schema::table('payment_deadlines', function (Blueprint $table) {
            $table->dropIndex('pd_company_context_idx');
            $table->dropColumn(['tenant_id', 'company_id', 'is_active']);
        });
    }
}
