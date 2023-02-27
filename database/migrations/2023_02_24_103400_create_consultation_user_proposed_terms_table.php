<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationUserProposedTermsTable extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_user_proposed_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_user_id')->constrained('consultation_user')->cascadeOnDelete();
            $table->dateTime('proposed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_user_proposed_terms');
    }
}
