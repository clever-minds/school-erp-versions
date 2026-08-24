<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use Throwable;
use DB;

use App\Repositories\Medium\MediumInterface;
use App\Repositories\Section\SectionInterface;
use App\Repositories\Subject\SubjectInterface;
use App\Repositories\Semester\SemesterInterface;
use App\Repositories\Stream\StreamInterface;
use App\Repositories\Shift\ShiftInterface;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Rules\uniqueForSchool;

class StaffAcademicsApiController extends Controller
{
    private MediumInterface $medium;
    private SectionInterface $section;
    private SubjectInterface $subject;
    private SemesterInterface $semester;
    private StreamInterface $stream;
    private ShiftInterface $shift;
    private ClassSchoolInterface $class;
    private ClassSectionInterface $classSection;

    public function __construct(
        MediumInterface $medium,
        SectionInterface $section,
        SubjectInterface $subject,
        SemesterInterface $semester,
        StreamInterface $stream,
        ShiftInterface $shift,
        ClassSchoolInterface $class,
        ClassSectionInterface $classSection
    ) {
        $this->medium = $medium;
        $this->section = $section;
        $this->subject = $subject;
        $this->semester = $semester;
        $this->stream = $stream;
        $this->shift = $shift;
        $this->class = $class;
        $this->classSection = $classSection;
    }

    // ==========================================
    // MEDIUM APIS
    // ==========================================

    public function mediumList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('medium-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = $request->search;
        $showDeleted = $request->show_deleted;

        try {
            $sql = $this->medium->builder()
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($q) use ($search) {
                        $q->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->Owner();
                    });
                })
                ->when(!empty($showDeleted), function ($q) {
                    $q->onlyTrashed()->Owner();
                });
            $total = $sql->count();

            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [
                'total' => $total,
                'data' => $res->toArray()
            ];
            return ResponseService::successResponse('Mediums fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> mediumList');
            return ResponseService::errorResponse();
        }
    }

    public function mediumStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('medium-create');
        $request->validate([
            'name' => ['required', new uniqueForSchool('mediums', 'name')]
        ]);
        try {
            $this->medium->create($request->except('_token'));
            return ResponseService::successResponse('Medium created successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> mediumStore');
            return ResponseService::errorResponse();
        }
    }

    public function mediumUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('medium-edit');
        $request->validate([
            'name' => ['required', new uniqueForSchool('mediums', 'name', $id)]
        ]);
        try {
            $this->medium->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Medium updated successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> mediumUpdate');
            return ResponseService::errorResponse();
        }
    }

    public function mediumDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('medium-delete');
        try {
            $this->medium->deleteById($id);
            return ResponseService::successResponse('Medium deleted successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> mediumDestroy');
            return ResponseService::errorResponse();
        }
    }
    // ==========================================
    // SECTION APIS
    // ==========================================

    public function sectionList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('section-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = $request->search;
        $showDeleted = $request->show_deleted;

        try {
            $sql = $this->section->builder()
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($q) use ($search) {
                        $q->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->Owner();
                    });
                })->when(!empty($showDeleted), function ($q) {
                    $q->onlyTrashed()->Owner();
                });
            $total = $sql->count();

            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [
                'total' => $total,
                'data' => $res->toArray()
            ];
            return ResponseService::successResponse('Sections fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> sectionList');
            return ResponseService::errorResponse();
        }
    }

    public function sectionStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('section-create');
        $request->validate([
            'name' => ['required', new uniqueForSchool('sections', 'name')]
        ]);
        try {
            $this->section->create($request->except('_token'));
            return ResponseService::successResponse('Section created successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> sectionStore');
            return ResponseService::errorResponse();
        }
    }

    public function sectionUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('section-edit');
        $request->validate([
            'name' => ['required', new uniqueForSchool('sections', 'name', $id)]
        ]);
        try {
            $this->section->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Section updated successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> sectionUpdate');
            return ResponseService::errorResponse();
        }
    }

    public function sectionDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('section-delete');
        try {
            $this->section->deleteById($id);
            return ResponseService::successResponse('Section deleted successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffAcademicsApiController -> sectionDestroy');
            return ResponseService::errorResponse();
        }
    }
    // ==========================================
    // SUBJECT APIS
    // ==========================================

    public function subjectList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('subject-list');
        try {
            $sql = $this->subject->builder()->with('medium');
            if ($request->search) {
                $search = $request->search;
                $sql->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->orwhere('type', 'LIKE', "%$search%");
            }
            $res = $sql->get();
            return ResponseService::successResponse('Subjects fetched successfully', $res->toArray());
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function subjectStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('subject-create');
        $request->validate([
            'medium_id' => 'required|numeric',
            'type'      => 'required|in:Practical,Theory',
            'name'      => ['required', new uniqueForSchool('subjects', ['name' => $request->name, 'medium_id' => $request->medium_id, 'type' => $request->type])]
        ]);
        try {
            $this->subject->create($request->except('_token'));
            return ResponseService::successResponse('Subject created successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function subjectUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('subject-edit');
        $request->validate([
            'medium_id' => 'required|numeric',
            'name'      => ['required', new uniqueForSchool('subjects', ['name' => $request->name, 'medium_id' => $request->medium_id, 'type' => $request->type], $id)]
        ]);
        try {
            $this->subject->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Subject updated successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function subjectDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('subject-delete');
        try {
            $this->subject->deleteById($id);
            return ResponseService::successResponse('Subject deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    // ==========================================
    // SEMESTER APIS
    // ==========================================

    public function semesterList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('semester-list');
        try {
            $res = $this->semester->builder()->get();
            return ResponseService::successResponse('Semesters fetched successfully', $res->toArray());
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function semesterStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('semester-create');
        $request->validate([
            'name' => ['required', new uniqueForSchool('semesters', 'name')],
            'start_month' => 'required|min:1,max:12',
            'end_month'   => 'required|min:1,max:12|different:start_month',
        ]);
        try {
            $this->semester->create($request->except('_token'));
            return ResponseService::successResponse('Semester created successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function semesterUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('semester-edit');
        $request->validate([
            'name' => ['required', new uniqueForSchool('semesters', 'name', $id)],
            'start_month' => 'required|min:1,max:12',
            'end_month'   => 'required|min:1,max:12|different:start_month',
        ]);
        try {
            $this->semester->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Semester updated successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function semesterDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('semester-delete');
        try {
            $this->semester->deleteById($id);
            return ResponseService::successResponse('Semester deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    // ==========================================
    // STREAM APIS
    // ==========================================

    public function streamList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('stream-list');
        try {
            $res = $this->stream->builder()->get();
            return ResponseService::successResponse('Streams fetched successfully', $res->toArray());
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function streamStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('stream-create');
        $request->validate([
            'name' => ['required', new uniqueForSchool('streams', 'name')]
        ]);
        try {
            $this->stream->create($request->except('_token'));
            return ResponseService::successResponse('Stream created successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function streamUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('stream-edit');
        $request->validate([
            'name' => ['required', new uniqueForSchool('streams', 'name', $id)]
        ]);
        try {
            $this->stream->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Stream updated successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function streamDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('stream-delete');
        try {
            $this->stream->deleteById($id);
            return ResponseService::successResponse('Stream deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    // ==========================================
    // SHIFT APIS
    // ==========================================

    public function shiftList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('shift-list');
        try {
            $res = $this->shift->builder()->get();
            return ResponseService::successResponse('Shifts fetched successfully', $res->toArray());
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function shiftStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('shift-create');
        $request->validate([
            'name' => ['required', new uniqueForSchool('shifts', 'name')],
            'start_time' => 'required',
            'end_time'   => 'required|after:start_time',
        ]);
        try {
            $this->shift->create($request->except('_token'));
            return ResponseService::successResponse('Shift created successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function shiftUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('shift-edit');
        $request->validate([
            'name' => ['required', new uniqueForSchool('shifts', 'name', $id)],
            'start_time' => 'required',
            'end_time'   => 'required|after:start_time',
        ]);
        try {
            $this->shift->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Shift updated successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function shiftDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('shift-delete');
        try {
            $this->shift->deleteById($id);
            return ResponseService::successResponse('Shift deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }
    // ==========================================
    // CLASS APIS
    // ==========================================

    public function classList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('class-list');
        try {
            $sql = $this->class->builder()->with('stream', 'medium', 'shift');
            if ($request->search) {
                $search = $request->search;
                $sql->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%");
            }
            $res = $sql->get();
            return ResponseService::successResponse('Classes fetched successfully', $res->toArray());
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function classStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('class-create');
        $request->validate([
            'medium_id' => 'required|numeric',
            'name'      => ['required', new uniqueForSchool('classes', ['name' => $request->name, 'medium_id' => $request->medium_id, 'stream_id' => $request->stream_id, 'shift_id' => $request->shift_id])]
        ]);
        try {
            $this->class->create($request->except('_token'));
            return ResponseService::successResponse('Class created successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function classUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('class-edit');
        $request->validate([
            'medium_id' => 'required|numeric',
            'name'      => ['required', new uniqueForSchool('classes', ['name' => $request->name, 'medium_id' => $request->medium_id, 'stream_id' => $request->stream_id, 'shift_id' => $request->shift_id], $id)]
        ]);
        try {
            $this->class->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Class updated successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function classDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('class-delete');
        try {
            $this->class->deleteById($id);
            return ResponseService::successResponse('Class deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    // ==========================================
    // CLASS SECTION APIS
    // ==========================================

    public function classSectionList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('class-section-list');
        try {
            $sql = $this->classSection->builder()->with('class.medium', 'section', 'class.stream', 'class.shift');
            if ($request->class_id) {
                $sql->where('class_id', $request->class_id);
            }
            $res = $sql->get();
            return ResponseService::successResponse('Class sections fetched successfully', $res->toArray());
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function classSectionStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('class-section-create');
        $request->validate([
            'class_id'   => 'required|numeric',
            'section_id' => 'required|array',
            'section_id.*' => 'numeric'
        ]);
        try {
            DB::beginTransaction();
            foreach ($request->section_id as $section) {
                $exists = $this->classSection->builder()->where('class_id', $request->class_id)->where('section_id', $section)->exists();
                if (!$exists) {
                    $this->classSection->create(['class_id' => $request->class_id, 'section_id' => $section]);
                }
            }
            DB::commit();
            return ResponseService::successResponse('Class section assigned successfully');
        } catch (Throwable $e) {
            DB::rollback();
            return ResponseService::errorResponse();
        }
    }

    public function classSectionUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('class-section-edit');
        $request->validate([
            'class_id'   => 'required|numeric',
            'section_id' => 'required|numeric',
        ]);
        try {
            $this->classSection->update($id, $request->except(['_token', 'id']));
            return ResponseService::successResponse('Class section updated successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function classSectionDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('class-section-delete');
        try {
            $this->classSection->deleteById($id);
            return ResponseService::successResponse('Class section deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    // ==========================================
    // CLASS TEACHER APIS
    // ==========================================

    public function classTeacherAssign(Request $request)
    {
        ResponseService::noPermissionThenSendJson('class-teacher-create');
        $request->validate([
            'class_section_id' => 'required|numeric',
            'teacher_id'       => 'required|array',
            'teacher_id.*'     => 'numeric'
        ]);
        try {
            DB::beginTransaction();
            // Usually ClassTeacher logic is handled via a ClassTeacher repository or directly inserting.
            // Based on ClassSectionController logic:
            foreach ($request->teacher_id as $teacher) {
                // Here we would use ClassTeacherRepository, but since it wasn't explicitly injected,
                // we can insert directly or assume $this->classSection has a method for it.
                DB::table('class_teachers')->updateOrInsert(
                    ['class_section_id' => $request->class_section_id, 'class_teacher_id' => $teacher]
                );
            }
            DB::commit();
            return ResponseService::successResponse('Class teacher assigned successfully');
        } catch (Throwable $e) {
            DB::rollback();
            return ResponseService::errorResponse();
        }
    }

    public function classTeacherRemove(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('class-teacher-delete');
        try {
            DB::table('class_teachers')->where('id', $id)->delete();
            return ResponseService::successResponse('Class teacher removed successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }
}
