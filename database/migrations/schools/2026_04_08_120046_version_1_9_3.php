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
        // remove session_year_id column from payroll_settings table
        Schema::table('payroll_settings', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_settings', 'session_year_id')) {
                $table->dropForeign(['session_year_id']);
                $table->dropColumn('session_year_id');
            }
        });


        // Step 1: Add new JSON columns and rename page_layout
        Schema::table('certificate_templates', function (Blueprint $table) {
            // First add the new JSON columns (nullable to allow existing rows)
            if (!Schema::hasColumn('certificate_templates', 'config_json')) {
                $table->json('config_json')->nullable()->after('height');
            }
            if (!Schema::hasColumn('certificate_templates', 'design_json')) {
                $table->json('design_json')->nullable()->after('config_json');
            }
            if (!Schema::hasColumn('certificate_templates', 'layout')) {
                $table->string('layout', 100)->nullable()->after('type');
            }
        });

        // Step 2: Migrate data
        $templates = DB::table('certificate_templates')->get();
        foreach ($templates as $template) {

            // Map configuration fields into JSON
            $config_json = json_encode([
                'user_image_shape' => $template->user_image_shape ?? 'Square',
                'image_size' => $template->image_size ?? '100',
            ]);

            // Map existing styles/fields/description into a new design JSON array representing elements
            $elements = [];

            // Add standard elements (school name, logo, issue date, etc.) based on `fields` array and `style` locations
            $fields = $template->fields ? explode(',', $template->fields) : [];
            $styles = $template->style ? json_decode($template->style, true) : [];

            foreach ($fields as $field) {
                $field = trim($field);
                if (empty($field)) continue;

                // Parse style string like: 'style="position:absolute; left: 145px;top: 255px"'
                $left = '0px';
                $top = '0px';
                if (isset($styles[$field]) && preg_match('/left:\s*([0-9.]+)px;top:\s*([0-9.]+)px/', $styles[$field], $matches)) {
                    $left = $matches[1] . 'px';
                    $top = $matches[2] . 'px';
                }

                $elements[] = [
                    'id' => uniqid(),
                    'type' => $field, // e.g. school_name, school_logo, signature
                    'x' => $left,
                    'y' => $top,
                    // other properties can be customized later in builder
                ];
            }

            // Description was a rich text field with {placeholders}.
            // This is harder to map perfectly to dynamic elements as it was a massive text block.
            // We will map the entire description block as a single "text" element so it isn't lost.
            if (!empty($template->description)) {
                $left = '145px';
                $top = '255px';
                if (isset($styles['description']) && preg_match('/left:\s*([0-9.]+)px;top:\s*([0-9.]+)px/', $styles['description'], $matches)) {
                    $left = $matches[1] . 'px';
                    $top = $matches[2] . 'px';
                }

                $elements[] = [
                    'id' => uniqid(),
                    'type' => 'description',
                    'content' => $template->description,
                    'x' => $left,
                    'y' => $top,
                ];
            }

            // Title
            if (isset($styles['title'])) {
                $left = '145px';
                $top = '290px';
                if (preg_match('/left:\s*([0-9.]+)px;top:\s*([0-9.]+)px/', $styles['title'], $matches)) {
                    $left = $matches[1] . 'px';
                    $top = $matches[2] . 'px';
                }
                $elements[] = [
                    'id' => uniqid(),
                    'type' => 'title',
                    'content' => $template->name, // Title text usually was template name or hardcoded. It was injected dynamically in view `<b>{{ $certificateTemplate->name }}</b>`
                    'x' => $left,
                    'y' => $top,
                ];
            }

            $design_json = json_encode([
                'elements' => $elements
            ]);

            DB::table('certificate_templates')
                ->where('id', $template->id)
                ->update([
                    'layout' => $template->page_layout ?? 'A4 Landscape',
                    'config_json' => $config_json,
                    'design_json' => $design_json
                ]);
        }

        // Step 3: Drop the old columns
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_templates', 'page_layout')) {
                $table->dropColumn('page_layout');
            }
            if (Schema::hasColumn('certificate_templates', 'user_image_shape')) {
                $table->dropColumn('user_image_shape');
            }
            if (Schema::hasColumn('certificate_templates', 'image_size')) {
                $table->dropColumn('image_size');
            }
            if (Schema::hasColumn('certificate_templates', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('certificate_templates', 'fields')) {
                $table->dropColumn('fields');
            }
            if (Schema::hasColumn('certificate_templates', 'style')) {
                $table->dropColumn('style');
            }
        });

        Schema::dropIfExists('certificate_assignments');

        Schema::create('certificate_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_template_id')->constrained('certificate_templates')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_type')->default('Student'); // Student | Staff
            $table->foreignId('class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->foreignId('session_year_id')->nullable()->constrained('session_years')->nullOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('roll_no')->nullable();
            $table->date('issued_at')->nullable();
            $table->timestamps();

            // Prevent duplicate assignment of same certificate to same user
            $table->unique(['certificate_template_id', 'user_id'], 'uq_cert_user');
        });


        Schema::dropIfExists('tasks');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->foreignId('assigned_by')->nullable()->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('is_admin_assigned')->default(0);
            $table->timestamps();
        });

        Schema::table('schools', function (Blueprint $table) {
            // Tracks per-step provisioning state as JSON
            if (!Schema::hasColumn('schools', 'progress')) {
                $table->json('progress')->nullable()->after('installed');
            }
            // Current human-readable step label
            if (!Schema::hasColumn('schools', 'provision_step')) {
                $table->string('provision_step', 100)->nullable()->after('progress');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['progress', 'provision_step']);
        });
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('certificate_assignments');

        // add session_year_id column to payroll_settings table
        Schema::table('payroll_settings', function (Blueprint $table) {
            // if column not exists
            if (!Schema::hasColumn('payroll_settings', 'session_year_id')) {
                $table->foreignId('session_year_id')->nullable()->after('school_id')->references('id')->on('session_years')->cascadeOnDelete();
            }
        });

        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->string('user_image_shape', 100)->nullable();
            $table->integer('image_size')->nullable();
            $table->text('description')->nullable();
            $table->text('fields')->nullable();
            $table->json('style')->nullable();

            if (Schema::hasColumn('certificate_templates', 'layout')) {
                $table->renameColumn('layout', 'page_layout');
            }
            if (Schema::hasColumn('certificate_templates', 'config_json')) {
                $table->dropColumn('config_json');
            }
            if (Schema::hasColumn('certificate_templates', 'design_json')) {
                $table->dropColumn('design_json');
            }
        });
    }
};
