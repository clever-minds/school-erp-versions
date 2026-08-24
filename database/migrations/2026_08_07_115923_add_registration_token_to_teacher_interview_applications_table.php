<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_interview_applications', function (Blueprint $table) {
            $table->string('registration_token')->nullable();
            $table->timestamp('registration_token_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_interview_applications', function (Blueprint $table) {
            $table->dropColumn(['registration_token', 'registration_token_expires_at']);
        });
    }
};
