<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Grades\GradesInterface;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class StaffGradeApiController extends Controller
{
    private GradesInterface $grades;

    public function __construct(GradesInterface $grades)
    {
        $this->grades = $grades;
    }

    public function index()
    {
        // Using grade-create permission as it seems to be the combined permission for Grades based on GradeController
        ResponseService::noPermissionThenSendJson('grade-create');
        try {
            $grades = $this->grades->all();
            return ResponseService::successResponse('Grades fetched successfully', $grades);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffGradeApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('grade-create');
        $request->validate([
            'grade_data' => 'required|array'
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->grade_data as $data) {
                $gradesData = array(
                    'starting_range' => $data['starting_range'],
                    'ending_range'   => $data['ending_range'],
                    'grade'          => $data['grades'], // Matches web controller key
                    'created_at'     => now(),
                );
                $this->grades->updateOrCreate(['id' => $data['id'] ?? null], $gradesData);
            }
            DB::commit();
            return ResponseService::successResponse('Grades Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffGradeApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('grade-delete');
        try {
            $this->grades->deleteById($id);
            return ResponseService::successResponse('Grade Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffGradeApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
