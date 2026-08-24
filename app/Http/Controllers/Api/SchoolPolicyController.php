<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolPolicy;
use App\Models\PolicyAcknowledgment;
use App\Models\Staff;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SchoolPolicyController extends Controller
{
    public function getPolicies()
    {
        try {
            $user = Auth::user();
            $policies = SchoolPolicy::where('school_id', $user->school_id)->get();
            $acknowledgments = PolicyAcknowledgment::where('staff_id', $user->staff->id)->pluck('policy_id')->toArray();

            $data = $policies->map(function ($policy) use ($acknowledgments) {
                return [
                    'id' => $policy->id,
                    'title' => $policy->title,
                    'description' => $policy->description,
                    'file_url' => $policy->file_url,
                    'is_acknowledged' => in_array($policy->id, $acknowledgments)
                ];
            });

            return ResponseService::successResponse('Policies fetched successfully', [
                'policies' => $data,
                'policy_completed' => (bool)$user->staff->policy_completed
            ]);
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e);
        }
    }

    public function acknowledgePolicy(Request $request)
    {
        $request->validate([
            'policy_id' => 'required|exists:school_policies,id'
        ]);

        try {
            $user = Auth::user();
            $staff = $user->staff;

            PolicyAcknowledgment::updateOrCreate(
                [
                    'staff_id' => $staff->id,
                    'policy_id' => $request->policy_id,
                    'school_id' => $user->school_id
                ],
                [
                    'acknowledged_at' => now()
                ]
            );

            // Check if all policies are acknowledged
            $totalPolicies = SchoolPolicy::where('school_id', $user->school_id)->count();
            $acknowledgedCount = PolicyAcknowledgment::where('staff_id', $staff->id)->count();

            if ($acknowledgedCount >= $totalPolicies) {
                $staff->update(['policy_completed' => 1]);
            }

            return ResponseService::successResponse('Policy acknowledged successfully');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e);
        }
    }
}
