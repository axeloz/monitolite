<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToTaskHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_history', function (Blueprint $table) {
            $table->foreign(['task_id'], 'task_history_ibfk_1')->references(['id'])->on('tasks')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_history', function (Blueprint $table) {
            $table->dropForeign('task_history_ibfk_1');
        });
    }
}
