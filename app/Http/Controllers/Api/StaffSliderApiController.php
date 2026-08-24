<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Sliders\SlidersInterface;
use App\Services\ResponseService;
use Throwable;

class StaffSliderApiController extends Controller
{
    private SlidersInterface $sliders;

    public function __construct(SlidersInterface $sliders)
    {
        $this->sliders = $sliders;
    }

    public function sliderList()
    {
        ResponseService::noPermissionThenSendJson('slider-list');
        
        $sliders = $this->sliders->builder()->get();
        return ResponseService::successResponse('Slider list retrieved successfully', $sliders);
    }

    public function sliderStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('slider-create');

        $request->validate([
            'image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
            'type'  => 'required|in:1,2,3',
            'link'  => 'required_if:type,2',
        ]);

        try {
            $this->sliders->create($request->except('_token'));
            return ResponseService::successResponse('Slider created successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffSliderApiController -> sliderStore");
            return ResponseService::errorResponse('An error occurred while creating the slider');
        }
    }

    public function sliderUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('slider-edit');

        $request->validate([
            'image' => 'nullable|mimes:jpeg,png,jpg|image|max:2048',
            'type'  => 'required|in:1,2,3',
            'link'  => 'required_if:type,2',
        ]);

        try {
            $this->sliders->update($id, $request->except('_token', 'edit_id'));
            return ResponseService::successResponse('Slider updated successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffSliderApiController -> sliderUpdate");
            return ResponseService::errorResponse('An error occurred while updating the slider');
        }
    }

    public function sliderDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('slider-delete');

        try {
            $this->sliders->deleteById($id);
            return ResponseService::successResponse('Slider deleted successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffSliderApiController -> sliderDestroy");
            return ResponseService::errorResponse('An error occurred while deleting the slider');
        }
    }
}
