<?php

use dacoto\EnvSet\Facades\EnvSet;
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
        Schema::dropIfExists('sch_subjects');
        Schema::dropIfExists('sch_sections');
        Schema::dropIfExists('sch_classes');
        Schema::dropIfExists('sch_streams');
        Schema::dropIfExists('sch_mediums');
        Schema::dropIfExists('sch_boards');

        Schema::create('sch_boards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->timestamps();
        });

        Schema::create('sch_mediums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->timestamps();
        });

        Schema::create('sch_streams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->timestamps();
        });

        Schema::create('sch_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sch_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sch_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('bg_color')->nullable();
            $table->string('image')->nullable();
            $table->string('type')->comment('Theory/Practical')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->timestamps();
        });


        Schema::dropIfExists('school_boards');

        Schema::create('school_boards', function (Blueprint $table) {
            $table->id();
            // school_id and board_id foriegn key and unique
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('sch_board_id')->constrained('sch_boards')->onDelete('cascade');
            $table->unique(['school_id', 'sch_board_id']);
            $table->timestamps();
        });

        Schema::dropIfExists('system_update_tracks');
        Schema::dropIfExists('system_update_runs');

        Schema::create('system_update_runs', static function (Blueprint $table) {
            $table->id();
            $table->uuid('run_id')->unique();
            $table->string('version_from')->nullable();
            $table->string('version_to')->nullable();
            $table->enum('status', [
                'pending',
                'extracting',
                'main_migration',
                'tenant_migration',
                'cache_optimization',
                'completed',
                'failed',
            ])->default('pending');
            $table->unsignedInteger('total_schools')->default(0);
            $table->unsignedInteger('completed_schools')->default(0);
            $table->unsignedInteger('failed_schools')->default(0);
            $table->json('log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('system_update_tracks', static function (Blueprint $table) {
            $table->id();
            $table->uuid('run_id');
            $table->unsignedBigInteger('school_id');
            $table->string('school_name');
            $table->string('database_name');
            $table->enum('status', [
                'queued',
                'migrating',
                'seeding',
                'completed',
                'failed',
            ])->default('queued');
            $table->enum('migration_status', [
                'pending',
                'running',
                'completed',
                'failed',
            ])->default('pending');
            $table->enum('seeder_status', [
                'pending',
                'running',
                'completed',
                'failed',
            ])->default('pending');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('error_message')->nullable();
            $table->string('failed_step')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('run_id')->references('run_id')->on('system_update_runs')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->index(['run_id', 'status']);
        });

        Schema::table('schools', function (Blueprint $table) {
            // Tracks per-step provisioning state as JSON
            // first check if column exist then skip
            if (!Schema::hasColumn('schools', 'progress')) {
                $table->json('progress')->nullable()->after('installed');
            }
            // Current human-readable step label
            if (!Schema::hasColumn('schools', 'provision_step')) {
                $table->string('provision_step', 100)->nullable()->after('progress');
            }
        });

        // update .env file REVERB_HOST=127.0.0.1 REVERB_SCHEME=http
        EnvSet::setKey('REVERB_HOST', '127.0.0.1');
        EnvSet::setKey('REVERB_SCHEME', 'http');
        EnvSet::save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['progress', 'provision_step']);
        });

        Schema::dropIfExists('system_update_tracks');
        Schema::dropIfExists('system_update_runs');

        Schema::dropIfExists('school_boards');

        Schema::dropIfExists('sch_subjects');
        Schema::dropIfExists('sch_sections');
        Schema::dropIfExists('sch_classes');
        Schema::dropIfExists('sch_streams');
        Schema::dropIfExists('sch_mediums');
        Schema::dropIfExists('sch_boards');
    }
};
