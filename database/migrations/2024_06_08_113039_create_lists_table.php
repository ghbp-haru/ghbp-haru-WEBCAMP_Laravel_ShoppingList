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
            $table->id();
            $table->string('name', 128)->comment('「買うもの」名');
            $table->unsignedBigInteger('shopping_list_id')->comment('このタスクの所有者');
            $table->foreign('hopping_list_id')->references('id')->on('users'); // 外部キー制約
            //$table->timestamps();
            $table->dateTime('created_at')->useCurrent()->comment('タスク完了日時');
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hopping_lists');
    }
}