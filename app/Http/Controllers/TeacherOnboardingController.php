<?php

namespace App\Http\Controllers;

use App\Models\TeacherOnboardingJd;
use App\Models\TeacherOnboardingQuestion;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Throwable;

class TeacherOnboardingController extends Controller
{
    public function jdIndex()
    {
        return view('teacher_onboarding.jd_index');
    }

    public function jdStore(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        try {
            TeacherOnboardingJd::updateOrCreate(
                ['school_id' => auth()->user()->school_id],
                ['title' => $request->title, 'description' => $request->description]
            );
            return ResponseService::successResponse('JD saved successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function jdShow()
    {
        $jd = TeacherOnboardingJd::where('school_id', auth()->user()->school_id)->first();
        return response()->json($jd);
    }

    public function questionIndex()
    {
        return view('teacher_onboarding.question_index');
    }

    public function questionStore(Request $request)
    {
        $request->validate([
            'question' => 'required',
            'option_a' => 'required',
            'option_b' => 'required',
            'option_c' => 'required',
            'option_d' => 'required',
            'answer' => 'required|in:a,b,c,d',
        ]);

        try {
            TeacherOnboardingQuestion::create([
                'question' => $request->question,
                'option_a' => $request->option_a,
                'option_b' => $request->option_b,
                'option_c' => $request->option_c,
                'option_d' => $request->option_d,
                'answer' => $request->answer,
                'school_id' => auth()->user()->school_id,
            ]);
            return ResponseService::successResponse('Question added successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function questionList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');

        $query = TeacherOnboardingQuestion::where('school_id', auth()->user()->school_id);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('question', 'LIKE', "%$search%");
        }

        $total = $query->count();
        $rows = $query->orderBy($sort, $order)->offset($offset)->limit($limit)->get();

        $rows = $rows->map(function ($row, $index) use ($offset) {
            return [
                'id' => $row->id,
                'no' => $offset + $index + 1,
                'question' => $row->question,
                'option_a' => $row->option_a,
                'option_b' => $row->option_b,
                'option_c' => $row->option_c,
                'option_d' => $row->option_d,
                'answer' => $row->answer,
                'operate' => '<a href="#" class="btn btn-xs btn-gradient-danger delete-data" title="Delete"><i class="fa fa-trash"></i></a>'
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    public function questionDelete($id)
    {
        try {
            TeacherOnboardingQuestion::where('id', $id)->where('school_id', auth()->user()->school_id)->delete();
            return ResponseService::successResponse('Question deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }
}
