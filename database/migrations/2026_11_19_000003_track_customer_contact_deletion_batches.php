<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class TrackCustomerContactDeletionBatches extends Migration
{
    public function up()
    {
        Schema::table('customers',function(Blueprint $t){$t->char('customer_deletion_batch',36)->nullable();});
        Schema::table('contact_names',function(Blueprint $t){$t->char('customer_deletion_batch',36)->nullable();$t->index(['tenant_id','customer_id','customer_deletion_batch'],'contact_customer_deletion_batch_idx');});
    }
    public function down()
    {
        Schema::table('contact_names',function(Blueprint $t){$t->dropIndex('contact_customer_deletion_batch_idx');$t->dropColumn('customer_deletion_batch');});
        Schema::table('customers',function(Blueprint $t){$t->dropColumn('customer_deletion_batch');});
    }
}
