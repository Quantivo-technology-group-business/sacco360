<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportBatchIdToClients extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('import_batch_id', 36)->nullable()->index()->after('external_id');
        });

        // Also create an import_batches log table
        Schema::create('client_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 36)->unique();
            $table->string('filename')->nullable();
            $table->string('import_type')->default('members');
            $table->integer('total_rows')->default(0);
            $table->integer('imported')->default(0);
            $table->integer('updated')->default(0);
            $table->integer('errors')->default(0);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('import_batch_id');
        });
        Schema::dropIfExists('client_import_batches');
    }
}
