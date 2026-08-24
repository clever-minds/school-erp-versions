<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\TeacherOnboardingJd;
use App\Models\TeacherOnboardingQuestion;
use App\Models\TeacherOnboardingResult;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TeacherOnboardingController extends Controller
{
    public function getJd()
    {
        try {
            $user = Auth::user();
            $jd = TeacherOnboardingJd::where('school_id', $user->school_id)->first();
            return ResponseService::successResponse('JD fetched successfully', $jd);
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function getQuestions()
    {
        try {
            $user = Auth::user();
            $questions = TeacherOnboardingQuestion::where('school_id', $user->school_id)
                ->inRandomOrder()
                ->limit(20)
                ->get();
            
            return ResponseService::successResponse('Questions fetched successfully', $questions);
        } catch (Throwable $e) {
            return ResponseService::errorResponse();
        }
    }

    public function submitTest(Request $request)
    {
        $request->validate([
            'answers' => 'required|array', // e.g., [{question_id: 1, answer: 'a'}, ...]
        ]);

        try {
            $user = Auth::user();
            $score = 0;
            $totalQuestions = count($request->answers);

            foreach ($request->answers as $resp) {
                $question = TeacherOnboardingQuestion::find($resp['question_id']);
                if ($question && $question->answer == $resp['answer']) {
                    $score++;
                }
            }

            // Passing criteria: e.g., 50%
            $status = ($score >= ($totalQuestions / 2)) ? 1 : 0;

            TeacherOnboardingResult::create([
                'user_id' => $user->id,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'status' => $status,
                'school_id' => $user->school_id
            ]);

            if ($status == 1) {
                Staff::where('user_id', $user->id)->update(['onboarding_completed' => 1]);
                return ResponseService::successResponse('Test passed! Onboarding completed.', ['score' => $score]);
            } else {
                return ResponseService::successResponse('Test failed. Please try again.', ['score' => $score]);
            }

        } catch (Throwable $e) {
            return ResponseService::errorResponse($e->getMessage());
        }
    }
}
