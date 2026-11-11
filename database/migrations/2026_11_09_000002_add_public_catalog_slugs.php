<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicCatalogSlugs extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('catalog_slug', 80)->nullable()->unique();
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->string('catalog_slug', 80)->nullable();
            $table->unique(['company_id', 'catalog_slug'], 'branches_company_catalog_slug_unique');
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique('branches_company_catalog_slug_unique');
            $table->dropColumn('catalog_slug');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['catalog_slug']);
            $table->dropColumn('catalog_slug');
        });
    }
}
