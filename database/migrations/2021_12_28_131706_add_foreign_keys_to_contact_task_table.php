<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToContactTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_task', function (Blueprint $table) {
            $table->foreign(['task_id'], 'contact_task_ibfk_1')->references(['id'])->on('tasks')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['contact_id'], 'contact_task_ibfk_2')->references(['id'])->on('contacts')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contact_task', function (Blueprint $table) {
            $table->dropForeign('contact_task_ibfk_1');
            $table->dropForeign('contact_task_ibfk_2');
        });
    }
}
