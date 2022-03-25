<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueIndexInConsultaionUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consultation_user', function (Blueprint $table) {
            $table->dropForeign('consultation_user_consultation_id_foreign');
            $table->dropForeign('consultation_user_user_id_foreign');
            $table->dropUnique('consultation_user_unique');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultation_user', function (Blueprint $table) {
            $table->unique(['user_id', 'consultation_id'], 'consultation_user_unique');
        });
    }
}
