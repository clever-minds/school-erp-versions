<?php

namespace App\Http\Controllers;

use App\Repositories\StudentPickup\StudentPickupInterface;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Throwable;

class StudentPickupController extends Controller
{
    private StudentPickupInterface $studentPickup;

    public function __construct(StudentPickupInterface $studentPickup)
    {
        $this->studentPickup = $studentPickup;
    }

    public function index()
    {
        ResponseService::noPermissionThenRedirect('student-pickup-list');
        return view('student_pickup.index');
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-pickup-list');
        try {
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');
            $search = $request->input('search');

            $query = $this->studentPickup->builder()->with(['student.user', 'parent', 'verifier', 'school']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('pickup_person_name', 'LIKE', "%$search%")
                      ->orWhere('otp', 'LIKE', "%$search%")
                      ->orWhereHas('student.user', function($q) use ($search) {
                          $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                      });
                });
            }

            $total = $query->count();
            $rows = $query->orderBy($sort, $order)->offset($offset)->limit($limit)->get();

            $bulkData = [
                'total' => $total,
                'rows'  => $rows->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'student_name' => $row->student->full_name,
                        'parent_name' => $row->parent->full_name,
                        'pickup_person_name' => $row->pickup_person_name,
                        'otp' => $row->otp,
                        'status' => $row->status,
                        'verified_by' => $row->verifier ? $row->verifier->full_name : '-',
                        'verified_at' => $row->verified_at,
                        'created_at' => $row->created_at,
                    ];
                }),
            ];

            return response()->json($bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            return response()->json(['error' => true, 'message' => 'Something went wrong']);
        }
    }
}
