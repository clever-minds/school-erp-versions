<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\OnlineExamQuestionChoice\OnlineExamQuestionChoiceInterface;
use App\Repositories\OnlineExamQuestionOption\OnlineExamQuestionOptionInterface;
use App\Repositories\OnlineExamStudentAnswer\OnlineExamStudentAnswerInterface;
use App\Repositories\StudentOnlineExamStatus\StudentOnlineExamStatusInterface;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use Throwable;

class StaffOnlineExamResultApiController extends Controller
{
    private StudentOnlineExamStatusInterface $studentOnlineExamStatus;
    private OnlineExamStudentAnswerInterface $onlineExamStudentAnswer;
    private OnlineExamQuestionChoiceInterface $onlineExamQuestionChoice;
    private OnlineExamQuestionOptionInterface $onlineExamQuestionOption;

    public function __construct(
        StudentOnlineExamStatusInterface $studentOnlineExamStatus,
        OnlineExamStudentAnswerInterface $onlineExamStudentAnswer,
        OnlineExamQuestionChoiceInterface $onlineExamQuestionChoice,
        OnlineExamQuestionOptionInterface $onlineExamQuestionOption
    ) {
        $this->studentOnlineExamStatus = $studentOnlineExamStatus;
        $this->onlineExamStudentAnswer = $onlineExamStudentAnswer;
        $this->onlineExamQuestionChoice = $onlineExamQuestionChoice;
        $this->onlineExamQuestionOption = $onlineExamQuestionOption;
    }

    public function show($id, Request $request)
    {
        ResponseService::noPermissionThenSendJson('online-exam-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');

        try {
            $sql = $this->studentOnlineExamStatus->builder()->with('student_data', 'online_exam.question_choice')->where(['online_exam_id' => $id, 'status' => 2]);
            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $student_attempt) {
                $exam_submitted_question_ids = $this->onlineExamStudentAnswer->builder()->where(['student_id' => $student_attempt->student_id, 'online_exam_id' => $student_attempt->online_exam_id])->pluck('question_id');

                $question_ids = $this->onlineExamQuestionChoice->builder()->whereIn('id', $exam_submitted_question_ids)->pluck('question_id');

                $exam_attempted_answers = $this->onlineExamStudentAnswer->builder()->where(['student_id' => $student_attempt->student_id, 'online_exam_id' => $student_attempt->online_exam_id])->pluck('option_id');

                //removes the question id of the question if one of the answer of particular question is wrong
                $question_ids_array = $question_ids->toArray();
                foreach ($question_ids_array as $question_id) {
                    $check_questions_answers_exists = $this->onlineExamQuestionOption->builder()->where(['question_id' => $question_id, 'is_answer' => 1])->whereNotIn('id', $exam_attempted_answers)->count();
                    if ($check_questions_answers_exists) {
                        unset($question_ids_array[array_search($question_id, $question_ids_array)]);
                    }
                }

                $exam_correct_answers_question_id = $this->onlineExamQuestionOption->builder()->where(['is_answer' => 1])->whereIn('id', $exam_attempted_answers)->whereIn('question_id', $question_ids_array)->pluck('question_id');

                // get the data of only attempted data
                $total_obtained_marks = $this->onlineExamQuestionChoice->builder()->select(DB::raw("sum(marks)"))->where('online_exam_id', $student_attempt->online_exam_id)->whereIn('question_id', $exam_correct_answers_question_id)->first();
                $total_obtained_marks = $total_obtained_marks['sum(marks)'] ?? 0;
                
                $total_marks = $this->onlineExamQuestionChoice->builder()->select(DB::raw("sum(marks)"))->where('online_exam_id', $student_attempt->online_exam_id)->first();
                $total_marks = $total_marks['sum(marks)'] ?? 0;

                $tempRow = [];
                $tempRow['student_id'] = $student_attempt->student_id;
                $tempRow['student_name'] = $student_attempt->student_data->full_name ?? '';
                $tempRow['marks'] = $total_obtained_marks . ' / ' . $total_marks;
                
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Online Exam Results fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffOnlineExamResultApiController -> show");
            return ResponseService::errorResponse();
        }
    }
}
