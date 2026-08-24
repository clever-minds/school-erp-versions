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
        $connection = 'school';

        if (!Schema::connection($connection)->hasTable('teacher_documents')) {
            Schema::connection($connection)->create('teacher_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->string('type'); // id_proof, address_proof, marksheet, degree, experience_letter
                $table->string('file_url');
                $table->tinyInteger('status')->default(0)->comment('0: Pending, 1: Approved, 2: Rejected');
                $table->text('rejection_reason')->nullable();
                $table->foreignId('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        foreach (['staffs', 'staff'] as $tableName) {
            try {
                if (Schema::connection($connection)->hasTable($tableName)) {
                    if (!Schema::connection($connection)->hasColumn($tableName, 'kyc_completed')) {
                        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($connection, $tableName) {
                            if (Schema::connection($connection)->hasColumn($tableName, 'onboarding_completed')) {
                                $table->boolean('kyc_completed')->default(0)->after('onboarding_completed');
                            } else {
                                $table->boolean('kyc_completed')->default(0);
                            }
                        });
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Could not process kyc_completed for {$tableName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'school';
        Schema::connection($connection)->dropIfExists('teacher_documents');
        
        foreach (['staffs', 'staff'] as $tableName) {
            if (Schema::connection($connection)->hasColumn($tableName, 'kyc_completed')) {
                Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                    $table->dropColumn('kyc_completed');
                });
            }
        }
    }
};
