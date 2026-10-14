<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddShareTransferTransactionType extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('share_transaction_types')) {
            return;
        }

        $exists = DB::table('share_transaction_types')->where('name', 'Transfer')->exists();
        if (!$exists) {
            DB::table('share_transaction_types')->insert([
                'name' => 'Transfer',
                'translated_name' => 'Transfer',
            ]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('share_transaction_types')) {
            DB::table('share_transaction_types')->where('name', 'Transfer')->delete();
        }
    }
}
