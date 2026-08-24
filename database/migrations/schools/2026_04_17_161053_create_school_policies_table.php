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
        if (!Schema::hasTable('school_policies')) {
            Schema::create('school_policies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('file_url')->nullable();
                $table->bigInteger('school_id')->unsigned();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('staff_policy_acknowledgments')) {
            Schema::create('staff_policy_acknowledgments', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('staff_id')->unsigned();
                $table->bigInteger('policy_id')->unsigned();
                $table->bigInteger('school_id')->unsigned();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();

                $table->foreign('staff_id')->references('id')->on('staffs')->onDelete('cascade');
                $table->foreign('policy_id')->references('id')->on('school_policies')->onDelete('cascade');
            });
        }

        if (!Schema::hasColumn('staffs', 'policy_completed')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->boolean('policy_completed')->default(0)->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_policy_acknowledgments');
        Schema::dropIfExists('school_policies');

        if (Schema::hasColumn('staffs', 'policy_completed')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->dropColumn('policy_completed');
            });
        }
    }
};
