<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('host');
            $table->enum('type', ['ping', 'http', 'dns', 'ftp']);
            $table->string('params')->nullable();
            $table->unsignedInteger('frequency');
            $table->unsignedTinyInteger('attempts')->default('0');
            $table->unsignedTinyInteger('active')->default(1);
            $table->unsignedTinyInteger('status')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->timestamps();
            $table->timestamp('executed_at')->nullable();

            $table->unique(['host', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
