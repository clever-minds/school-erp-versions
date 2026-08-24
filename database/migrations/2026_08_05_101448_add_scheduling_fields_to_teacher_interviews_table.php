<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_interviews', function (Blueprint $table) {
            $table->time('time')->nullable()->after('interview_date');
            $table->string('location')->nullable()->after('time');
            $table->text('instructions')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_interviews', function (Blueprint $table) {
            $table->dropColumn(['time', 'location', 'instructions']);
        });
    }
};
