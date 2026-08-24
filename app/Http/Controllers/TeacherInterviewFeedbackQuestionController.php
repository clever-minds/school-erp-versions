<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewFeedbackQuestion;
use App\Models\TeacherInterviewCategory;
use App\Models\AuditOptionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewFeedbackQuestionController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('teacher-interview-question-list')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->wantsJson()) {
            $offset = request('offset', 0);
            $limit = request('limit', 10);
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');
            $search = request('search');

            $query = TeacherInterviewFeedbackQuestion::with('category');

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('feedback_question', 'like', "%{$search}%")
                      ->orWhereHas('category', function($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $total = $query->count();
            $query->orderBy($sort, $order)->skip($offset)->take($limit);
            $questions = $query->get();

            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            $no = 1;

            foreach ($questions as $question) {
                $operate = '<button class="btn btn-sm btn-info btn-rounded btn-icon edit-btn" data-id="' . $question->id . '" title="' . __('Edit') . '"><i class="fa fa-edit"></i></button>&nbsp;';
                
                $operate .= '<form action="' . route('teacher-interview-feedback-questions.destroy', $question->id) . '" method="POST" style="display:inline;" onsubmit="return confirm(\'' . __('Are you sure you want to delete this question?') . '\');">';
                $operate .= csrf_field();
                $operate .= method_field('DELETE');
                $operate .= '<button type="submit" class="btn btn-sm btn-danger btn-rounded btn-icon" title="' . __('Delete') . '"><i class="fa fa-trash"></i></button>';
                $operate .= '</form>';

                $statusBadge = $question->status == 'active' ? '<span class="badge badge-success">' . __('Active') . '</span>' : '<span class="badge badge-danger">' . __('Inactive') . '</span>';

                $tempRow = $question->toArray();
                $tempRow['no'] = $no++;
                $tempRow['category_name'] = $question->category->name ?? '-';
                $tempRow['status_badge'] = $statusBadge;
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }

        $questions = TeacherInterviewFeedbackQuestion::orderBy('id', 'desc')->get();
        $categories = TeacherInterviewCategory::where('status', 1)->get();
        return view('teacher-interview-feedback-questions.index', compact('questions', 'categories'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-question-create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'feedback_question' => 'required|string|max:255',
            'teacher_interview_category_id' => 'nullable|exists:teacher_interview_categories,id',
            'status' => 'required|in:active,inactive',
            'type' => 'nullable|string',
            'custom_options' => 'nullable|string',
            'audit_option_group_id' => 'nullable|exists:audit_option_groups,id'
        ]);

        TeacherInterviewFeedbackQuestion::create($request->all());

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question created successfully.'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-question-edit')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'feedback_question' => 'required|string|max:255',
            'teacher_interview_category_id' => 'nullable|exists:teacher_interview_categories,id',
            'status' => 'required|in:active,inactive',
            'type' => 'nullable|string',
            'custom_options' => 'nullable|string',
            'audit_option_group_id' => 'nullable|exists:audit_option_groups,id'
        ]);

        $question = TeacherInterviewFeedbackQuestion::findOrFail($id);
        $question->update($request->all());

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question updated successfully.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('teacher-interview-question-delete')) {
            abort(403, 'Unauthorized action.');
        }

        $question = TeacherInterviewFeedbackQuestion::findOrFail($id);
        $question->delete();

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question deleted successfully.'));
    }
}
