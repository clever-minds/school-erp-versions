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
        if (!Schema::hasColumn('school_audit_answers', 'remarks')) {
            Schema::table('school_audit_answers', function (Blueprint $table) {
                $table->text('remarks')->nullable()->after('answer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_audit_answers', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
