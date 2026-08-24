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
            $table->dropColumn('options');
            $table->unsignedBigInteger('audit_option_group_id')->nullable()->after('type');
            $table->foreign('audit_option_group_id')->references('id')->on('audit_option_groups')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_questions', function (Blueprint $table) {
            $table->dropForeign(['audit_option_group_id']);
            $table->dropColumn('audit_option_group_id');
            $table->json('options')->nullable()->after('type');
        });
    }
};
