<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnnulmentFieldsToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('annulment_status', [
                'none',
                'waiting_sunat_process',
                'pending',
                'accepted',
                'rejected',
                'requires_credit_note'
            ])->default('none')->after('state_annulled');

            $table->string('annulment_type')->nullable()->after('annulment_status');

            $table->text('annulment_reason')->nullable()->after('annulment_type');
            $table->timestamp('annulment_requested_at')->nullable()->after('annulment_reason');
            $table->timestamp('annulment_accepted_at')->nullable()->after('annulment_requested_at');

            $table->text('annulment_error')->nullable()->after('annulment_accepted_at');
            $table->string('annulment_ticket')->nullable()->after('annulment_error');
            $table->longText('annulment_response')->nullable()->after('annulment_ticket');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'annulment_status',
                'annulment_type',
                'annulment_reason',
                'annulment_requested_at',
                'annulment_accepted_at',
                'annulment_error',
                'annulment_ticket',
                'annulment_response',
            ]);
        });
    }
}
