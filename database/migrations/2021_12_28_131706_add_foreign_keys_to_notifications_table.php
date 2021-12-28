<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreign(['contact_id'], 'contact_id_frgn')->references(['id'])->on('contacts')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['task_history_id'], 'task_history_id_frgn')->references(['id'])->on('task_history')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign('contact_id_frgn');
            $table->dropForeign('task_history_id_frgn');
        });
    }
}
