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
        Schema::table('audit_questions', function (Blueprint $table) {
            if (Schema::hasColumn('audit_questions', 'category')) {
                $table->dropColumn('category');
            }
            if (!Schema::hasColumn('audit_questions', 'audit_category_id')) {
                $table->foreignId('audit_category_id')->nullable()->constrained('audit_categories')->onDelete('cascade')->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_questions', function (Blueprint $table) {
            if (Schema::hasColumn('audit_questions', 'audit_category_id')) {
                $table->dropForeign(['audit_category_id']);
                $table->dropColumn('audit_category_id');
            }
            if (!Schema::hasColumn('audit_questions', 'category')) {
                $table->string('category')->nullable();
            }
        });
    }
};
