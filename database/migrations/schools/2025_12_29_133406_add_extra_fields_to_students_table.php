<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('blood_group', 10)->nullable()->after('application_status');
            $table->string('nationality')->nullable()->after('blood_group');
            $table->string('birth_place')->nullable()->after('nationality');
            $table->string('last_school')->nullable()->after('birth_place');
            $table->string('last_cleared_class')->nullable()->after('last_school');
            $table->string('education_board')->nullable()->after('last_cleared_class');
            $table->text('remarks')->nullable()->after('education_board');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'blood_group',
                'nationality',
                'birth_place',
                'last_school',
                'last_cleared_class',
                'education_board',
                'remarks',
            ]);
        });
    }
};
