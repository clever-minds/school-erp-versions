<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\Repositories\Exam\ExamInterface;
use App\Repositories\ExamResult\ExamResultInterface;
use App\Repositories\ExamMarks\ExamMarksInterface;
use App\Repositories\Student\StudentInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ExamResult;
use Throwable;
use Str;

class StaffExamResultApiController extends Controller
{
    private ExamInterface $exam;
    private ExamResultInterface $examResult;
    private ExamMarksInterface $examMarks;
    private StudentInterface $student;

    public function __construct(
        ExamInterface $exam,
        ExamResultInterface $examResult,
        ExamMarksInterface $examMarks,
        StudentInterface $student
    ) {
        $this->exam = $exam;
        $this->examResult = $examResult;
        $this->examMarks = $examMarks;
        $this->student = $student;
    }

    public function showExamResult(Request $request)
    {
        ResponseService::noPermissionThenSendJson('exam-result');
        $request->validate([
            'exam_id' => 'required',
            'session_year_id' => 'required'
        ]);

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        try {
            $sql = $this->examResult->builder()->with([
                'user:id,first_name,last_name,school_id',
                'user.exam_marks' => function ($q) use ($request) {
                    $q->whereHas('timetable', function ($q) use ($request) {
                        $q->where('exam_id', $request->exam_id);
                    })->with('timetable', 'subject');
                }
            ])->where('exam_id', $request->exam_id)
                ->where('session_year_id', $request->session_year_id)
                ->when($search, function ($q) use ($search, $request) {
                    $q->where(function ($q) use ($search) {
                        $q->where('id', 'LIKE', "%$search%")
                            ->orwhere('total_marks', 'LIKE', "%$search%")
                            ->orwhere('grade', 'LIKE', "%$search%")
                            ->orwhere('obtained_marks', 'LIKE', "%$search%")
                            ->orwhere('percentage', 'LIKE', "%$search%")
                            ->orWhereHas('user', function ($q) use ($search) {
                                $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                            });
                    })->where('exam_id', $request->exam_id);
                });

            if ($request->class_section_id) {
                $sql = $sql->where('class_section_id', $request->class_section_id);
            }

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [
                'total' => $total,
                'data' => $res->toArray()
            ];

            return ResponseService::successResponse('Exam results fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffExamResultApiController -> showExamResult");
            return ResponseService::errorResponse();
        }
    }

    private function resultStatus($examId, $studentId)
    {
        $examMarks = $this->examMarks->builder()->where('student_id', $studentId)
            ->whereHas('timetable', function ($q) use ($examId) {
                $q->where('exam_id', $examId);
            })->get();

        $status = 1;
        foreach ($examMarks as $marks) {
            if ($marks->passing_status == 0) {
                $status = 0;
                break;
            }
        }
        return $status;
    }

    public function publishExamResult($id)
    {
        ResponseService::noPermissionThenSendJson('exam-result');
        try {
            // Get The Exam Data with Marks and Timetable
            $exam = $this->exam->builder()->with([
                'marks' => function ($query) {
                    $query->with('user:id,first_name,last_name,image', 'user.student:id,user_id,class_section_id')->selectRaw('SUM(obtained_marks) as total_obtained_marks, student_id')->selectRaw('SUM(total_marks) as total_marks')->groupBy('student_id');
                },
                'timetable:id,exam_id,start_time,end_time'
            ])->with([
                'timetable' => function ($q) {
                    $q->with('exam_marks');
                }
            ])->where('school_id', Auth::user()->school_id)->findOrFail($id);

            $allSubjectsSubmitted = true;
            foreach ($exam->timetable as $timetable) {
                if ($timetable->exam_marks->isEmpty()) {
                    $allSubjectsSubmitted = false;
                    break;
                }
            }

            if (!$allSubjectsSubmitted) {
                return ResponseService::errorResponse("Marks are not uploaded yet.");
            }

            DB::beginTransaction();
            if ($exam->exam_status == 2 && $exam->marks->isNotEmpty()) {

                if ($exam->publish == 0) {
                    // If exam is Unpublished then Insert ExamResult records and Publish the Exam
                    $examResult = $exam->marks->map(function ($examMarks) use ($exam, $id) {
                        $percentage = ($examMarks['total_obtained_marks'] * 100) / $examMarks['total_marks'];
                        $grade = findExamGrade($percentage);

                        if ($grade === null) {
                            throw new \Exception("Grades data does not exists");
                        }

                        $status = $this->resultStatus($id, $examMarks['student_id']);

                        $data = [
                            'exam_id' => $exam->id,
                            'class_section_id' => $examMarks['user']['student']['class_section_id'],
                            'student_id' => $examMarks['student_id'],
                            'total_marks' => $examMarks['total_marks'],
                            'obtained_marks' => $examMarks['total_obtained_marks'],
                            'percentage' => round($percentage, 2),
                            'grade' => $grade,
                            'status' => $status,
                            'session_year_id' => $exam->session_year_id
                        ];
                        return $data;
                    });

                    $studentIds = $examResult->pluck('student_id')->toArray();
                    $guardian_id = $this->student->builder()->with('user')->whereIn('user_id', $studentIds)->pluck('guardian_id')->toArray();

                    $this->examResult->createBulk($examResult->toArray());
                    $this->exam->update($id, ['publish' => 1]);

                    $user = array_unique(array_merge($studentIds, $guardian_id));

                    $title = 'Result Publish for ' . $exam->name . ' examinations !!!';
                    $body = 'Congrats your result has been publish Click here see your result ';
                    $type = "exam result";

                    if (function_exists('send_notification')) {
                        send_notification($user, $title, $body, $type);
                    }
                    
                    DB::commit();
                    return ResponseService::successResponse('Exam results published successfully');

                } else {
                    ExamResult::where('exam_id', $id)->delete();
                    $this->exam->update($id, ['publish' => 0]);
                    
                    DB::commit();
                    return ResponseService::successResponse('Exam results unpublished successfully');
                }
            } else {
                DB::rollBack();
                return ResponseService::errorResponse('Exam not completed yet');
            }

        } catch (Throwable $e) {
            DB::rollBack();
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents', 'Grades data does not exists'])) {
                return ResponseService::errorResponse($e->getMessage());
            }
            ResponseService::logErrorResponse($e, "StaffExamResultApiController -> publishExamResult");
            return ResponseService::errorResponse();
        }
    }

    public function updateExamResultMarks(Request $request)
    {
        ResponseService::noPermissionThenSendJson('exam-result-edit');
        $request->validate([
            'edit.*.marks_id' => 'required|numeric',
            'edit.*.obtained_marks' => 'required|numeric|lte:edit.*.total_marks',
            'edit.*.passing_marks' => 'required|numeric',
            'edit.*.total_marks' => 'required|numeric',
            'edit.*.exam_id' => 'required|numeric',
            'edit.*.student_id' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->edit as $data) {
                $passingMarks = $data['passing_marks'];
                $marksPercentage = ($data['obtained_marks'] / $data['total_marks']) * 100;

                $grade = findExamGrade($marksPercentage);
                if ($grade == null) {
                    return ResponseService::errorResponse("Grades data does not exists");
                }

                $updateMarksData = array(
                    'obtained_marks' => $data['obtained_marks'],
                    'passing_status' => $data['obtained_marks'] >= $passingMarks ? 1 : 0,
                    'grade' => $grade
                );

                $this->examMarks->update($data['marks_id'], $updateMarksData);

                $examResultId = $this->examResult->builder()->where(['exam_id' => $data['exam_id'], 'student_id' => $data['student_id']])->value('id');

                $exam = $this->exam->builder()->with([
                    'marks' => function ($query) use ($data) {
                        $query->with('user.student:id,user_id,class_section_id')
                            ->selectRaw('SUM(obtained_marks) as total_obtained_marks,student_id')
                            ->selectRaw('SUM(total_marks) as total_marks')
                            ->selectRaw('MIN(CASE WHEN passing_status = 0 THEN 0 ELSE 1 END) as overall_passing_status')
                            ->where('student_id', $data['student_id'])
                            ->groupBy('student_id');
                    },
                    'timetable' => function ($query) use ($data) {
                        $query->where(['exam_id' => $data['exam_id']]);
                    }
                ])->where('id', $data['exam_id'])->first();

                foreach ($exam->marks as $examMarks) {
                    $percentage = ($examMarks['total_obtained_marks'] * 100) / $examMarks['total_marks'];

                    $grade = findExamGrade($percentage);
                    if ($grade == null) {
                        return ResponseService::errorResponse("Grades data does not exists");
                    }

                    $examResultData = array(
                        "obtained_marks" => $examMarks['total_obtained_marks'], 
                        "percentage" => round($percentage, 2), 
                        "grade" => $grade, 
                        "status" => $examMarks['overall_passing_status']
                    );

                    if ($examResultId) {
                        $this->examResult->update($examResultId, $examResultData);
                    }
                }
            }
            DB::commit();
            return ResponseService::successResponse("Marks Updated Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffExamResultApiController -> updateExamResultMarks");
            return ResponseService::errorResponse();
        }
    }
}
