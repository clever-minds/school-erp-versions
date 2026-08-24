<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = 'school';

        // 1. teacher_onboarding_jds
        if (!Schema::connection($connection)->hasTable('teacher_onboarding_jds')) {
            Schema::connection($connection)->create('teacher_onboarding_jds', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description');
                $table->bigInteger('school_id')->unsigned();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. teacher_onboarding_questions
        if (!Schema::connection($connection)->hasTable('teacher_onboarding_questions')) {
            Schema::connection($connection)->create('teacher_onboarding_questions', function (Blueprint $table) {
                $table->id();
                $table->text('question');
                $table->text('option_a');
                $table->text('option_b');
                $table->text('option_c');
                $table->text('option_d');
                $table->string('answer'); // 'a', 'b', 'c', or 'd'
                $table->bigInteger('school_id')->unsigned();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. teacher_onboarding_results
        if (!Schema::connection($connection)->hasTable('teacher_onboarding_results')) {
            Schema::connection($connection)->create('teacher_onboarding_results', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id')->unsigned();
                $table->integer('score');
                $table->integer('total_questions');
                $table->boolean('status')->default(0); // 0: Fail/Ongoing, 1: Pass
                $table->bigInteger('school_id')->unsigned();
                $table->timestamps();
            });
        }

        // 4. Add onboarding_completed column to staffs (or staff) table
        foreach (['staffs', 'staff'] as $tableName) {
            try {
                if (Schema::connection($connection)->hasTable($tableName)) {
                    if (!Schema::connection($connection)->hasColumn($tableName, 'onboarding_completed')) {
                        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($connection, $tableName) {
                            // Only use 'after' if user_id column exists
                            if (Schema::connection($connection)->hasColumn($tableName, 'user_id')) {
                                $table->boolean('onboarding_completed')->default(0)->after('user_id');
                            } else {
                                $table->boolean('onboarding_completed')->default(0);
                            }
                        });
                        Log::info("Added onboarding_completed column to {$tableName} table on connection {$connection}.");
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Could not process onboarding_completed for {$tableName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'school';
        Schema::connection($connection)->dropIfExists('teacher_onboarding_jds');
        Schema::connection($connection)->dropIfExists('teacher_onboarding_questions');
        Schema::connection($connection)->dropIfExists('teacher_onboarding_results');
        
        foreach (['staffs', 'staff'] as $tableName) {
            if (Schema::connection($connection)->hasColumn($tableName, 'onboarding_completed')) {
                Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                    $table->dropColumn('onboarding_completed');
                });
            }
        }
    }
};
