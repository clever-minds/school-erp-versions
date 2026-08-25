<?php

use App\Models\ClassSubject;
use App\Models\ExamMarks;
use App\Models\LessonCommon;
use App\Models\Slider;
use App\Models\Syllabus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $sessionYearTables = [
        'class_subjects',
        'elective_subject_groups',
        'class_teachers',
        'subject_teachers',
        'timetables',
        'semesters',
        'certificate_templates',
        'payroll_settings',
        'staff_salaries'
    ];

    private array $uniqueIndexTables = [
        'class_subjects' => [
            'old_unique' => ['class_id', 'subject_id', 'virtual_semester_id'],
            'new_unique' => ['class_id', 'subject_id', 'virtual_semester_id', 'session_year_id'],
            'fk_index' => ['class_id', 'subject_id', 'virtual_semester_id'],
        ],
        'class_teachers' => [
            'old_unique' => ['class_section_id', 'teacher_id'],
            'new_unique' => ['class_section_id', 'teacher_id', 'session_year_id'],
            'fk_index' => ['class_section_id', 'teacher_id'],
        ],
        'subject_teachers' => [
            'old_unique' => ['class_section_id', 'class_subject_id', 'teacher_id'],
            'new_unique' => ['class_section_id', 'class_subject_id', 'teacher_id', 'session_year_id'],
            'fk_index' => ['class_section_id', 'class_subject_id', 'teacher_id'],
        ],
        'staff_salaries' => [
            'old_unique' => ['staff_id', 'payroll_setting_id'],
            'new_unique' => ['staff_id', 'payroll_setting_id', 'session_year_id'],
            'fk_index' => ['staff_id', 'payroll_setting_id'],
        ]
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection('school');

        // ---------------------------------------------------------------------------------------------------------
        // 1. From: 2026_03_10_182811_add_session_year_id_to_tables.php & 2026_03_16_161143_add_session_year_id_to_tables_v2.php
        // Process session_year_id additions to tables
        // ---------------------------------------------------------------------------------------------------------

        foreach ($this->sessionYearTables as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            if ($table === 'staff_salaries') {
                if (!$schema->hasColumn('staff_salaries', 'school_id')) {
                    $schema->table('staff_salaries', function (Blueprint $table) {
                        $table->foreignId('school_id')->nullable()->after('percentage')->references('id')->on('schools')->onDelete('cascade');
                    });
                }

                if (!$schema->hasColumn('staff_salaries', 'session_year_id')) {
                    $schema->table('staff_salaries', function (Blueprint $table) {
                        $table->foreignId('session_year_id')->nullable()->after('school_id')->references('id')->on('session_years')->onDelete('cascade');
                    });
                }

                // Backfill school_id from staff -> user 
                DB::connection('school')->statement("
                    UPDATE staff_salaries ss
                    JOIN staffs s ON ss.staff_id = s.id
                    JOIN users u ON s.user_id = u.id
                    SET ss.school_id = u.school_id
                    WHERE ss.school_id IS NULL
                ");
            } else {
                if (!$schema->hasColumn($table, 'session_year_id')) {
                    $schema->table($table, function (Blueprint $table) {
                        $table->unsignedBigInteger('session_year_id')
                            ->nullable()
                            ->before('created_at');

                        $table->foreign('session_year_id')
                            ->references('id')
                            ->on('session_years')
                            ->cascadeOnDelete();
                    });
                }
            }
        }

        // ---------------------------------------------------------------------------------------------------------
        // 2. From: 2026_03_10_182847_backfill_session_year_id.php & 2026_03_16_161258_backfill_session_year_id_v2.php
        // Backfill session_year_id data from school settings
        // ---------------------------------------------------------------------------------------------------------

        foreach ($this->sessionYearTables as $table) {
            if ($schema->hasTable($table) && $schema->hasColumn($table, 'session_year_id')) {
                DB::connection('school')->statement("
                    UPDATE {$table} t
                    JOIN school_settings ss
                    ON t.school_id = ss.school_id
                    SET t.session_year_id = ss.data
                    WHERE ss.name = 'session_year'
                    AND t.session_year_id IS NULL
                ");
            }
        }

        // ---------------------------------------------------------------------------------------------------------
        // 3. From: 2026_03_10_182920_update_unique_indexes_with_session_year.php & 2026_03_17_191434_update_unique_indexes_with_session_year_v2.php
        // Update specific table unique indexes to include session_year_id without breaking foreign keys
        // ---------------------------------------------------------------------------------------------------------

        foreach ($this->uniqueIndexTables as $table => $config) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            // A. Find & drop FK constraints depending on unique_ids
            $droppedFks = $this->dropFksDependingOnIndex($table, 'unique_ids');

            // B. Add temporary plain indexes on FK columns
            foreach ($config['fk_index'] as $col) {
                $idxName = "idx_{$table}_{$col}";
                $exists = DB::connection('school')->select("
                    SHOW INDEX FROM `{$table}` WHERE Key_name = ?
                ", [$idxName]);

                if (empty($exists) && $schema->hasColumn($table, $col)) {
                    DB::connection('school')->statement("
                        ALTER TABLE `{$table}` ADD INDEX `{$idxName}` (`{$col}`)
                    ");
                }
            }

            // C. Drop old unique index
            $index = DB::connection('school')->select("
                SHOW INDEX FROM `{$table}` WHERE Key_name = 'unique_ids'
            ");

            $indexs = DB::connection('school')->select("
                SHOW INDEX FROM `{$table}` WHERE Key_name = 'unique_id'
            ");

            if (!empty($index)) {
                DB::connection('school')->statement("
                    ALTER TABLE `{$table}` DROP INDEX `unique_ids`
                ");
            }

            if (!empty($indexs)) {
                DB::connection('school')->statement("
                    ALTER TABLE `{$table}` DROP INDEX `unique_id`
                ");
            }

            // D. Add new unique index with session year
            $indexName = (!empty($index)) ? 'unique_ids' : 'unique_id';
            $columns = '`' . implode('`,`', $config['new_unique']) . '`';

            DB::connection('school')->statement("
                ALTER TABLE `{$table}` ADD UNIQUE `{$indexName}` ({$columns})
            ");

            // E. Re-add dropped FK constraints
            foreach ($droppedFks as $fk) {
                DB::connection('school')->statement("
                    ALTER TABLE `{$table}`
                    ADD CONSTRAINT `{$fk['name']}`
                        FOREIGN KEY (`{$fk['column']}`)
                        REFERENCES `{$fk['ref_table']}` (`{$fk['ref_column']}`)
                        ON DELETE {$fk['on_delete']}
                        ON UPDATE {$fk['on_update']}
                ");
            }

            // F. Clean up temporary plain indexes
            foreach ($config['fk_index'] as $col) {
                $idxName = "idx_{$table}_{$col}";
                $exists = DB::connection('school')->select("
                    SHOW INDEX FROM `{$table}` WHERE Key_name = ?
                ", [$idxName]);

                if (!empty($exists)) {
                    try {
                        DB::connection('school')->statement("SET FOREIGN_KEY_CHECKS = 0;");
                        DB::connection('school')->statement("
                            ALTER TABLE `{$table}` DROP INDEX `{$idxName}`
                        ");
                        DB::connection('school')->statement("SET FOREIGN_KEY_CHECKS = 1;");
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        // ---------------------------------------------------------------------------------------------------------
        // 4. From: 2026_03_14_000000_add_semester_id_to_assignments_table.php
        // Bind session tracking elements: add semester_id constraints
        // ---------------------------------------------------------------------------------------------------------

        if ($schema->hasTable('assignments') && !$schema->hasColumn('assignments', 'semester_id')) {
            $schema->table('assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('semester_id')->nullable()->after('extra_days_for_resubmission');
                $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
            });
        }

        // ---------------------------------------------------------------------------------------------------------
        // 5. From: 2026_03_14_091924_slider1_9_2.php
        // Modify default statuses config (sliders and exam_marks)
        // ---------------------------------------------------------------------------------------------------------

        Slider::where('type', 4)->update(['type' => 1]);

        if ($schema->hasTable('exam_marks') && !$schema->hasColumn('exam_marks', 'status')) {
            $schema->table('exam_marks', function (Blueprint $table) {
                $table->tinyInteger('status')->default(0)->comment('0 => Draft, 1 => Published')->after('obtained_marks');
            });
            ExamMarks::whereNotNull('id')->update(['status' => 1]);
        }

        // ---------------------------------------------------------------------------------------------------------
        // 6. From: 2026_03_24_093944_create_syllabi_table.php
        // Generate entirely new syllabus table and seed structure
        // ---------------------------------------------------------------------------------------------------------

        if (!$schema->hasTable('syllabus')) {
            $schema->create('syllabus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->nullable(true)->constrained()->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable(true)->constrained()->cascadeOnDelete();
                $table->string('title')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->unique(['title'], 'syllabus_title_unique');
            });

            if ($schema->hasTable('class_subjects')) {
                $classSubjects = ClassSubject::with('class.stream', 'class.medium', 'class.shift', 'subject')->get();
                $data = [];
                foreach ($classSubjects as $classSubject) {
                    if ($classSubject->class) {
                        $titleParts = [
                            $classSubject->class->name,
                            optional($classSubject->class->stream)->name,
                            optional($classSubject->class->medium)->name,
                            optional($classSubject->class->shift)->name,
                        ];
                        $data[] = [
                            'class_id' => $classSubject->class_id,
                            'subject_id' => $classSubject->subject_id,
                            'title' => implode(' ', array_filter($titleParts)) . ' - ' . ($classSubject->subject->name ?? ''),
                            'status' => 'active',
                        ];
                    }
                }
                if (!empty($data)) {
                    Syllabus::upsert($data, ['title'], ['class_id', 'subject_id', 'status']);
                }
            }
        }

        // ---------------------------------------------------------------------------------------------------------
        // 7. From: 2026_03_24_102821_alter_class_subjects_and_lesson_lesson_commons_table.php
        // Alter internal linking with class/lessons -> attach syllabus entries
        // ---------------------------------------------------------------------------------------------------------

        Schema::disableForeignKeyConstraints();

        if ($schema->hasTable('class_subjects') && !$schema->hasColumn('class_subjects', 'syllabus_id')) {
            $schema->table('class_subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('syllabus_id')->nullable()->after('subject_id');
                $table->foreign('syllabus_id')->references('id')->on('syllabus')->onDelete('cascade');
            });
        }

        if ($schema->hasTable('lesson_commons') && !$schema->hasColumn('lesson_commons', 'syllabus_id')) {
            $schema->table('lesson_commons', function (Blueprint $table) {
                $table->foreignId('syllabus_id')->nullable(true)->after('class_section_id')->references('id')->on('syllabus')->cascadeOnDelete();
            });
        }

        Schema::enableForeignKeyConstraints();

        if ($schema->hasTable('syllabus') && $schema->hasTable('class_subjects')) {
            $syllabuses = Syllabus::all();
            foreach ($syllabuses as $syllabus) {
                ClassSubject::where('class_id', $syllabus->class_id)->where('subject_id', $syllabus->subject_id)->update([
                    'syllabus_id' => $syllabus->id,
                ]);
            }
        }

        if ($schema->hasTable('class_subjects') && $schema->hasTable('lesson_commons')) {
            // if class_subject_id column found in lesson_commons table
            if ($schema->hasColumn('lesson_commons', 'class_subject_id')) {
                $classSubjects = ClassSubject::get();
                foreach ($classSubjects as $classSubject) {
                    if ($classSubject->syllabus_id) {
                        LessonCommon::where('class_subject_id', $classSubject->id)->update([
                            'syllabus_id' => $classSubject->syllabus_id,
                        ]);
                    }
                }
            }
        }

        // ---------------------------------------------------------------------------------------------------------
        // 8. From: 2026_03_24_161925_remove_class_subject_id_column.php
        // Prune old associations matching newly integrated syllabus system
        // ---------------------------------------------------------------------------------------------------------

        if ($schema->hasTable('lesson_commons') && $schema->hasColumn('lesson_commons', 'class_subject_id')) {
            try {
                $schema->table('lesson_commons', function (Blueprint $table) {
                    $table->dropForeign(['class_subject_id']);
                });
            } catch (\Exception $e) {
            }

            $schema->table('lesson_commons', function (Blueprint $table) {
                $table->dropColumn('class_subject_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('school');

        // ---------------------------------------------------------------------------------------------------------
        // Rollback 8. Restore class_subject_id to lesson_commons 
        // ---------------------------------------------------------------------------------------------------------

        if ($schema->hasTable('lesson_commons') && !$schema->hasColumn('lesson_commons', 'class_subject_id')) {
            $schema->table('lesson_commons', function (Blueprint $table) {
                $table->foreignId('class_subject_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        // ---------------------------------------------------------------------------------------------------------
        // Rollback 7. Drop syllabus bindings 
        // ---------------------------------------------------------------------------------------------------------

        Schema::disableForeignKeyConstraints();

        if ($schema->hasTable('class_subjects') && $schema->hasColumn('class_subjects', 'syllabus_id')) {
            $schema->table('class_subjects', function (Blueprint $table) {
                $table->dropForeign(['syllabus_id']);
                $table->dropColumn('syllabus_id');
            });
        }

        if ($schema->hasTable('lesson_commons') && $schema->hasColumn('lesson_commons', 'syllabus_id')) {
            $schema->table('lesson_commons', function (Blueprint $table) {
                $table->dropForeign(['syllabus_id']);
                $table->dropColumn('syllabus_id');
            });
        }

        Schema::enableForeignKeyConstraints();

        // ---------------------------------------------------------------------------------------------------------
        // Rollback 6. Evict syllabus structure
        // ---------------------------------------------------------------------------------------------------------

        if ($schema->hasTable('syllabus')) {
            $schema->dropIfExists('syllabus');
        }

        // ---------------------------------------------------------------------------------------------------------
        // Rollback 5. Revert slider type and exam marks status
        // ---------------------------------------------------------------------------------------------------------

        if ($schema->hasTable('exam_marks') && $schema->hasColumn('exam_marks', 'status')) {
            $schema->table('exam_marks', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        // ---------------------------------------------------------------------------------------------------------
        // Rollback 4. Remove semester_id from assignments
        // ---------------------------------------------------------------------------------------------------------

        if ($schema->hasTable('assignments') && $schema->hasColumn('assignments', 'semester_id')) {
            $schema->table('assignments', function (Blueprint $table) {
                $table->dropForeign(['semester_id']);
                $table->dropColumn('semester_id');
            });
        }

        // ---------------------------------------------------------------------------------------------------------
        // Rollback 3. Reset index constraints without session_year_id element 
        // ---------------------------------------------------------------------------------------------------------

        foreach ($this->uniqueIndexTables as $table => $config) {
            if (!$schema->hasTable($table) || !$schema->hasColumn($table, 'session_year_id')) {
                continue;
            }

            $droppedFks = $this->dropFksDependingOnIndex($table, 'unique_ids');

            $index = DB::connection('school')->select("
                SHOW INDEX FROM `{$table}` WHERE Key_name = 'unique_ids'
            ");

            if (!empty($index)) {
                DB::connection('school')->statement("
                    ALTER TABLE `{$table}` DROP INDEX `unique_ids`
                ");
            }

            $columns = '`' . implode('`,`', $config['old_unique']) . '`';

            DB::connection('school')->statement("
                ALTER TABLE `{$table}` ADD UNIQUE `unique_ids` ({$columns})
            ");

            foreach ($droppedFks as $fk) {
                DB::connection('school')->statement("
                    ALTER TABLE `{$table}`
                    ADD CONSTRAINT `{$fk['name']}`
                        FOREIGN KEY (`{$fk['column']}`)
                        REFERENCES `{$fk['ref_table']}` (`{$fk['ref_column']}`)
                        ON DELETE {$fk['on_delete']}
                        ON UPDATE {$fk['on_update']}
                ");
            }
        }

        // ---------------------------------------------------------------------------------------------------------
        // Rollback 1 & 2. Purge session_year context dependencies and columns 
        // ---------------------------------------------------------------------------------------------------------

        // Nullify session_year_id first to erase trace
        foreach ($this->sessionYearTables as $table) {
            if ($schema->hasTable($table) && $schema->hasColumn($table, 'session_year_id')) {
                DB::connection('school')->statement("
                    UPDATE {$table} SET session_year_id = NULL
                ");
            }
        }

        // Drop physical columns and foreign constraint
        foreach ($this->sessionYearTables as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            if ($table === 'staff_salaries') {
                $schema->table('staff_salaries', function (Blueprint $table) use ($schema) {
                    if ($schema->hasColumn('staff_salaries', 'session_year_id')) {
                        try {
                            $table->dropForeign(['session_year_id']);
                        } catch (\Exception $e) {
                        }
                        $table->dropColumn('session_year_id');
                    }
                    if ($schema->hasColumn('staff_salaries', 'school_id')) {
                        try {
                            $table->dropForeign(['school_id']);
                        } catch (\Exception $e) {
                        }
                        $table->dropColumn('school_id');
                    }
                });
            } else {
                if ($schema->hasColumn($table, 'session_year_id')) {
                    $schema->table($table, function (Blueprint $table) {
                        try {
                            $table->dropForeign(['session_year_id']);
                        } catch (\Exception $e) {
                        }
                        $table->dropColumn('session_year_id');
                    });
                }
            }
        }
    }

    /**
     * Finds all FK constraints on $table whose supporting index is $indexName,
     * drops them, and returns their definitions so they can be restored later.
     */
    private function dropFksDependingOnIndex(string $table, string $indexName): array
    {
        $database = DB::connection('school')->getDatabaseName();

        $fks = DB::connection('school')->select("
            SELECT
                kcu.CONSTRAINT_NAME        AS name,
                kcu.COLUMN_NAME            AS `column`,
                kcu.REFERENCED_TABLE_NAME  AS ref_table,
                kcu.REFERENCED_COLUMN_NAME AS ref_column,
                rc.DELETE_RULE             AS on_delete,
                rc.UPDATE_RULE             AS on_update
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_NAME   = kcu.CONSTRAINT_NAME
               AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ?
              AND kcu.TABLE_NAME   = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);

        $dropped = [];

        foreach ($fks as $fk) {
            $inIndex = DB::connection('school')->select("
                SHOW INDEX FROM `{$table}`
                WHERE Key_name = ? AND Column_name = ?
            ", [$indexName, $fk->column]);

            if (!empty($inIndex)) {
                DB::connection('school')->statement("
                    ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->name}`
                ");

                $dropped[] = [
                    'name' => $fk->name,
                    'column' => $fk->column,
                    'ref_table' => $fk->ref_table,
                    'ref_column' => $fk->ref_column,
                    'on_delete' => $fk->on_delete,
                    'on_update' => $fk->on_update,
                ];
            }
        }

        return $dropped;
    }
};
