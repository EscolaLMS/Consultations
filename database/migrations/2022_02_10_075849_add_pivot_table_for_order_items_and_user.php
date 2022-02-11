<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPivotTableForOrderItemsAndUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (
            Schema::hasTable('orders') &&
            Schema::hasColumns('orders', ['executed_at', 'executed_status'])
        ) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['executed_at', 'executed_status']);
            });
        }
        Schema::create('consultations_terms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('order_item_id')->unsigned();
            $table->dateTime('executed_at')->nullable();
            $table->string('executed_status')->nullable();

            if (Schema::hasTable('orders')) {
                $table->foreign('order_item_id')->on('orders')->references('id')->cascadeOnDelete();
            }
            $table->foreign('user_id')->on('users')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (
            Schema::hasTable('orders') &&
            !Schema::hasColumns('orders', ['executed_at', 'executed_status'])
        ) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dateTime('executed_at')->nullable();
                $table->string('executed_status')->nullable();
            });
        }

        Schema::dropIfExists('consultations_terms');
    }
}
