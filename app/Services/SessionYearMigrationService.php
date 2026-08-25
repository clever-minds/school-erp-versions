<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\ElectiveSubjectGroup;
use App\Models\Fee;
use App\Models\FeesClassType;
use App\Models\FeesInstallment;
use App\Models\LeaveMaster;
use App\Models\Semester;
use App\Models\SubjectTeacher;
use App\Models\SessionYear;
use App\Models\StaffSalary;
use App\Models\Timetable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SessionYearMigrationService
{
    /**
     * Migrate data from one session year to another.
     *
     * @param int $fromSessionYearId
     * @param int $toSessionYearId
     * @param array $options
     * @param int $schoolId
     * @param array|null $semesterData
     * @return void
     * @throws Throwable
     */
    public function migrate(int $fromSessionYearId, int $toSessionYearId, array $options, int $schoolId, ?array $semesterData = null): void
    {
        DB::beginTransaction();
        try {
            $semesterMapping = [];
            $classSubjectMapping = [];
            $subjectTeacherMapping = [];

            // 1. Semesters (if selected)
            if (in_array('semesters', $options)) {
                $semesterMapping = $this->migrateSemesters($fromSessionYearId, $toSessionYearId, $schoolId, $semesterData);
            }

            // 2. Class Subjects (and Elective Groups)
            if (in_array('class_subjects', $options)) {
                $classSubjectMapping = $this->migrateClassSubjects($fromSessionYearId, $toSessionYearId, $schoolId, $semesterMapping);
            }

            // 3. Teachers (Class & Subject)
            if (in_array('teachers', $options)) {
                $subjectTeacherMapping = $this->migrateTeachers($fromSessionYearId, $toSessionYearId, $schoolId, $classSubjectMapping);
            }

            // 4. Timetables
            if (in_array('class_timetables', $options)) {
                $this->migrateTimetables($fromSessionYearId, $toSessionYearId, $schoolId, $subjectTeacherMapping, $semesterMapping);
            }

            // 5. Fees
            if (in_array('fees', $options)) {
                $this->migrateFees($fromSessionYearId, $toSessionYearId, $schoolId);
            }

            // 6. Leave Settings
            if (in_array('leave_settings', $options)) {
                $this->migrateLeaveSettings($fromSessionYearId, $toSessionYearId, $schoolId);
            }

            // 7. Staff Payroll Settings
            if (in_array('staff_payroll_settings', $options)) {
                $this->migrateStaffPayrollSettings($fromSessionYearId, $toSessionYearId, $schoolId);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function migrateSemesters(int $fromId, int $toId, int $schoolId, ?array $semesterData): array
    {
        $mapping = [];
        $oldSemesters = Semester::where('session_year_id', $fromId)->where('school_id', $schoolId)->get();
        foreach ($oldSemesters as $oldSemester) {
            $newSemester = $oldSemester->replicate();
            $newSemester->session_year_id = $toId;

            if ($semesterData && isset($semesterData[$oldSemester->id])) {
                $newSemester->start_date = \Carbon\Carbon::createFromFormat('d-m-Y', $semesterData[$oldSemester->id]['start_date'])->format('Y-m-d');
                $newSemester->end_date = \Carbon\Carbon::createFromFormat('d-m-Y', $semesterData[$oldSemester->id]['end_date'])->format('Y-m-d');
            }

            $newSemester->save();
            $mapping[$oldSemester->id] = $newSemester->id;
        }
        return $mapping;
    }

    private function migrateClassSubjects(int $fromId, int $toId, int $schoolId, array $semesterMapping): array
    {
        $classSubjectMapping = [];

        // 1. Migrate Elective Subject Groups first
        $electiveGroupMapping = [];
        ElectiveSubjectGroup::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldGroups) use ($toId, $semesterMapping, &$electiveGroupMapping) {
            foreach ($oldGroups as $oldGroup) {
                $newGroup = $oldGroup->replicate();
                $newGroup->session_year_id = $toId;
                // Map semester_id if it exists
                if ($oldGroup->semester_id && isset($semesterMapping[$oldGroup->semester_id])) {
                    $newGroup->semester_id = $semesterMapping[$oldGroup->semester_id];
                }
                $newGroup->save();
                $electiveGroupMapping[$oldGroup->id] = $newGroup->id;
            }
        });

        // 2. Migrate Class Subjects
        ClassSubject::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldClassSubjects) use ($toId, $semesterMapping, $electiveGroupMapping, &$classSubjectMapping) {
            foreach ($oldClassSubjects as $oldCS) {
                $newCS = $oldCS->replicate(['virtual_semester_id']);
                $newCS->session_year_id = $toId;

                // Map semester_id if it was migrated
                if ($oldCS->semester_id && isset($semesterMapping[$oldCS->semester_id])) {
                    $newCS->semester_id = $semesterMapping[$oldCS->semester_id];
                }

                // $newCS->virtual_semester_id = $newCS->semester_id ?? 0;

                // Map elective_subject_group_id if it was migrated
                if ($oldCS->elective_subject_group_id && isset($electiveGroupMapping[$oldCS->elective_subject_group_id])) {
                    $newCS->elective_subject_group_id = $electiveGroupMapping[$oldCS->elective_subject_group_id];
                }

                $newCS->save();
                $classSubjectMapping[$oldCS->id] = $newCS->id;
            }
        });

        return $classSubjectMapping;
    }

    private function migrateTeachers(int $fromId, int $toId, int $schoolId, array $classSubjectMapping): array
    {
        $subjectTeacherMapping = [];

        // 1. Migrate Class Teachers
        ClassTeacher::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldClassTeachers) use ($toId) {
            foreach ($oldClassTeachers as $oldCT) {
                $newCT = $oldCT->replicate();
                $newCT->session_year_id = $toId;
                $newCT->save();
            }
        });

        // 2. Migrate Subject Teachers
        SubjectTeacher::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldSubjectTeachers) use ($toId, $classSubjectMapping, &$subjectTeacherMapping) {
            foreach ($oldSubjectTeachers as $oldST) {
                // Only migrate if the class subject was migrated
                if (isset($classSubjectMapping[$oldST->class_subject_id])) {
                    $newST = $oldST->replicate();
                    $newST->session_year_id = $toId;
                    $newST->class_subject_id = $classSubjectMapping[$oldST->class_subject_id];
                    $newST->save();
                    $subjectTeacherMapping[$oldST->id] = $newST->id;
                }
            }
        });

        return $subjectTeacherMapping;
    }

    private function migrateTimetables(int $fromId, int $toId, int $schoolId, array $subjectTeacherMapping, array $semesterMapping): void
    {
        Timetable::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldTimetables) use ($toId, $subjectTeacherMapping, $semesterMapping) {
            foreach ($oldTimetables as $oldTT) {
                $newTT = $oldTT->replicate();
                $newTT->session_year_id = $toId;

                // Map Semester ID
                if ($oldTT->semester_id && isset($semesterMapping[$oldTT->semester_id])) {
                    $newTT->semester_id = $semesterMapping[$oldTT->semester_id];
                }

                // Handle Lectures
                if ($oldTT->type == 'Lecture') {
                    // Map subject_teacher_id if the teacher assignment was migrated
                    if ($oldTT->subject_teacher_id && isset($subjectTeacherMapping[$oldTT->subject_teacher_id])) {
                        $newTT->subject_teacher_id = $subjectTeacherMapping[$oldTT->subject_teacher_id];
                    }
                    // subject_id is session-neutral, stays the same
                    // class_section_id is session-neutral, stays the same
                }

                $newTT->save();
            }
        });
    }

    private function migrateFees(int $fromId, int $toId, int $schoolId): void
    {
        $toSessionYear = SessionYear::findOrFail($toId);
        $newDueDate = $toSessionYear->getRawOriginal('end_date');

        // 1. Migrate Fees (Main records)
        Fee::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldFees) use ($toId, $newDueDate) {
            foreach ($oldFees as $oldFee) {
                $newFee = $oldFee->replicate();
                $newFee->session_year_id = $toId;
                $newFee->due_date = $newDueDate; // Reset to session end date
                $newFee->save();

                // 2. Migrate FeesClassType (Links to this specific fee)
                $oldClassTypes = FeesClassType::where('fees_id', $oldFee->id)->get();
                foreach ($oldClassTypes as $oldCT) {
                    $newCT = $oldCT->replicate();
                    $newCT->fees_id = $newFee->id;
                    $newCT->save();
                }

                // 3. Migrate FeesInstallment (Links to this specific fee)
                $oldInstallments = FeesInstallment::where('fees_id', $oldFee->id)->get();
                foreach ($oldInstallments as $oldInst) {
                    $newInst = $oldInst->replicate();
                    $newInst->session_year_id = $toId;
                    $newInst->fees_id = $newFee->id;
                    $newInst->due_date = $newDueDate; // Reset to session end date
                    $newInst->save();
                }
            }
        });
    }

    private function migrateLeaveSettings(int $fromId, int $toId, int $schoolId): void
    {
        LeaveMaster::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldLeaves) use ($toId) {
            foreach ($oldLeaves as $oldL) {
                $newL = $oldL->replicate();
                $newL->session_year_id = $toId;
                $newL->save();
            }
        });
    }

    private function migrateStaffPayrollSettings(int $fromId, int $toId, int $schoolId): void
    {
        StaffSalary::where('session_year_id', $fromId)->where('school_id', $schoolId)->chunk(100, function ($oldSettings) use ($toId) {
            foreach ($oldSettings as $oldS) {
                $newS = $oldS->replicate();
                $newS->session_year_id = $toId;
                $newS->save();
            }
        });
    }
}
