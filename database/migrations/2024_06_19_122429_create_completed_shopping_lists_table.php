<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompletedShoppingListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('completed_shopping_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->comment('「買うもの」名');
            $table->unsignedBigInteger('user_id')->comment('この買うものリストの所有者');
            $table->foreign('user_id')->references('id')->on('users'); // 外部キー制約
            //$table->timestamps();
            // $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('completed_shopping_lists');
    }
}
