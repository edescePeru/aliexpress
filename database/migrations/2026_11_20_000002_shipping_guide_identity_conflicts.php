<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShippingGuideIdentityConflicts extends Migration
{
    public function up()
    {
        Schema::create('shipping_guide_identity_conflicts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shipping_guide_id')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('reason', 500);
            $table->longText('evidence_encrypted');
            $table->string('evidence_sha256', 64);
            $table->string('recorded_by', 24);
            $table->timestamp('created_at');
        });
        Schema::table('shipping_guides', function (Blueprint $table) {
            $table->timestamp('identity_conflict_at')->nullable();
        });
    }

    public function down()
    {
        throw new \RuntimeException('No retirar automáticamente la evidencia y los bloqueos de conflictos. Revisar y respaldar manualmente.');
    }
}
