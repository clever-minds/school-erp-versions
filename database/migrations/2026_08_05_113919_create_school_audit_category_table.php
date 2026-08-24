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
        Schema::create('school_audit_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_audit_id')->constrained('school_audits')->onDelete('cascade');
            $table->foreignId('audit_category_id')->constrained('audit_categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_audit_category');
    }
};
