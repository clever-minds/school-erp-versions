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
        Schema::table('optional_fees', function (Blueprint $table) {
            $table->string('remark')->nullable()->after('fees_paid_id');
        });

        Schema::table('compulsory_fees', function (Blueprint $table) {
            $table->string('remark')->nullable()->after('fees_paid_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('optional_fees', function (Blueprint $table) {
            $table->dropColumn('remark');
        });

        Schema::table('compulsory_fees', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};
