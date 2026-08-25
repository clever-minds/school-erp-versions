<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchBoard;
use App\Models\SchMedium;
use App\Models\SchStream;
use App\Models\SchClass;
use App\Models\SchSection;
use App\Models\SchSubject;
use App\Models\SystemSetting;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Auth;

class AcademySetupController extends Controller
{
    private $models = [
        'boards' => SchBoard::class,
        'mediums' => SchMedium::class,
        'streams' => SchStream::class,
        'classes' => SchClass::class,
        'sections' => SchSection::class,
        'subjects' => SchSubject::class,
    ];

    private CachingService $cache;

    public function __construct(CachingService $cache)
    {
        $this->cache = $cache;
    }

    private function getStats()
    {
        return [
            'boards' => SchBoard::count(),
            'mediums' => SchMedium::count(),
            'streams' => SchStream::count(),
            'classes' => SchClass::count(),
            'sections' => SchSection::count(),
            'subjects' => SchSubject::count(),
        ];
    }

    public function index()
    {
        ResponseService::noPermissionThenRedirect('academy-setup-list');

        $systemSettings = $this->cache->getSystemSettings();
        $isMasterEnabled = $systemSettings['academy_master_status'] ?? 0;

        $stats = [
            'boards' => SchBoard::count(),
            'mediums' => SchMedium::count(),
            'streams' => SchStream::count(),
            'classes' => SchClass::count(),
            'sections' => SchSection::count(),
            'subjects' => SchSubject::count(),
        ];

        return view('super_admin.academy-setup.index', compact('isMasterEnabled', 'stats'));
    }

    public function show(Request $request, $type)
    {
        ResponseService::noPermissionThenRedirect('academy-setup-list');

        if (!array_key_exists($type, $this->models)) {
            ResponseService::errorResponse('Invalid type');
        }

        $model = $this->models[$type];

        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;

        // Default sort for classes and sections is sort_order
        $defaultSort = ($type === 'classes' || $type === 'sections') ? 'sort_order' : 'id';
        $defaultOrder = ($type === 'classes' || $type === 'sections') ? 'ASC' : 'DESC';

        $sort = $request->sort ?? $defaultSort;
        $order = $request->order ?? $defaultOrder;
        $search = $request->search;

        $sql = $model::query();

        if ($search) {
            $sql->where(function ($q) use ($search, $type) {
                $q->where('name', 'LIKE', "%$search%");
                if ($type === 'boards' || $type === 'subjects') {
                    $q->orWhere('code', 'LIKE', "%$search%");
                }
            });
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = $offset + 1;

        foreach ($res as $row) {
            $operate = '';
            if (Auth::user()->can('academy-setup-edit')) {
                $operate .= BootstrapTableService::button('fa fa-edit', '#', ['edit-data', 'btn-action-edit'], ['title' => trans("edit"), 'data-id' => $row->id]);
            }

            if (Auth::user()->can('academy-setup-delete')) {
                $operate .= BootstrapTableService::button('fa fa-times', route('academy-setup.destroy', ['type' => $type, 'id' => $row->id]), ['academy-delete-btn', 'btn-action-hard-delete'], ['title' => trans("delete"), 'data-message' => trans('you_want_to_delete_this_record'), 'data-id' => $row->id]);
            }

            $statusText = $row->status == 1 ? '<span class="badge badge-success">ACTIVE</span>' : '<span class="badge badge-danger">INACTIVE</span>';

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['formatted_status'] = $statusText;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function store(Request $request, $type)
    {
        ResponseService::noPermissionThenSendJson('academy-setup-create');

        if (!array_key_exists($type, $this->models)) {
            ResponseService::errorResponse('Invalid type');
        }

        $model = $this->models[$type];
        $tableName = (new $model)->getTable();

        $rules = [
            'name' => 'required|unique:' . $tableName . ',name',
        ];

        if ($type === 'boards' || $type === 'subjects') {
            $rules['code'] = 'required|unique:' . $tableName . ',code';
        }

        if ($type === 'subjects') {
            $rules['bg_color'] = 'required';
            $rules['image'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $model = $this->models[$type];
            $data = [
                'name' => $request->name,
                'status' => $request->status ? 1 : 0
            ];

            if ($type === 'boards' || $type === 'subjects') {
                $data['code'] = $request->code;
            }

            if ($type === 'subjects') {
                $data['bg_color'] = $request->bg_color;
                $data['type'] = $request->subject_type;

                if ($request->hasFile('image')) {
                    $data['image'] = $request->file('image')->store('subjects', 'public');
                }
            }

            if ($type === 'classes' || $type === 'sections') {
                $data['sort_order'] = $model::count() + 1;
            }

            $model::create($data);

            ResponseService::successResponse('Data Stored Successfully', null, ['stats' => $this->getStats()]);
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e->getMessage());
            ResponseService::errorResponse('something_went_wrong');
        }
    }

    public function update(Request $request, $type, $id)
    {
        ResponseService::noPermissionThenSendJson('academy-setup-edit');

        if (!array_key_exists($type, $this->models)) {
            ResponseService::errorResponse('Invalid type');
        }

        $modelClass = $this->models[$type];
        $tableName = (new $modelClass)->getTable();

        $rules = [
            'name' => 'required|unique:' . $tableName . ',name,' . $id,
        ];

        if ($type === 'boards' || $type === 'subjects') {
            $rules['code'] = 'required|unique:' . $tableName . ',code,' . $id;
        }

        if ($type === 'subjects') {
            $rules['bg_color'] = 'required|unique:' . $tableName . ',bg_color,' . $id;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $modelClass = $this->models[$type];
            $model = $modelClass::findOrFail($id);

            $model->name = $request->name;
            $model->status = $request->status ? 1 : 0;

            if ($type === 'boards' || $type === 'subjects') {
                $model->code = $request->code;
            }

            if ($type === 'subjects') {
                $model->bg_color = $request->bg_color;
                $model->type = $request->subject_type;

                if ($request->hasFile('image')) {
                    $model->image = $request->file('image')->store('subjects', 'public');
                }
            }

            $model->save();

            ResponseService::successResponse('Data Updated Successfully');
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e->getMessage());
            ResponseService::errorResponse('something_went_wrong');
        }
    }

    public function destroy($type, $id)
    {
        ResponseService::noPermissionThenSendJson('academy-setup-delete');

        if (!array_key_exists($type, $this->models)) {
            ResponseService::errorResponse('Invalid type');
        }
        try {
            $modelClass = $this->models[$type];
            $model = $modelClass::findOrFail($id);
            $model->delete();

            // If delete all then update master status to 0, and apply condition for only current $type
            $counnt = $modelClass::count();
            if ($counnt == 0) {
                SystemSetting::updateOrCreate(
                    ['name' => 'academy_master_status'],
                    [
                        'data' => 0,
                        'type' => 'integer'
                    ]
                );
            }

            // clear system settings cache
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));

            ResponseService::successResponse('Data Deleted Successfully', null, ['stats' => $this->getStats()]);
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e->getMessage());
            ResponseService::errorResponse('something_went_wrong');
        }
    }

    public function updateMasterStatus(Request $request)
    {
        ResponseService::noPermissionThenSendJson('academy-setup-edit');

        try {
            $status = $request->status ? 1 : 0;

            if ($status == 1) {
                // Check basic data configured or not
                $stats = [
                    'boards' => SchBoard::count(),
                    'mediums' => SchMedium::count(),
                    'streams' => SchStream::count(),
                    'classes' => SchClass::count(),
                    'sections' => SchSection::count(),
                    'subjects' => SchSubject::count(),
                ];

                if (in_array(0, $stats)) {
                    ResponseService::errorResponse('Please configure all the basic data');
                }
            }

            SystemSetting::updateOrCreate(
                ['name' => 'academy_master_status'],
                [
                    'data' => $status,
                    'type' => 'integer'
                ]
            );
            // clear system settings cache
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Exception $e) {
            return response()->json(['error' => true, 'message' => __('something_went_wrong')]);
        }
    }

    public function reorder(Request $request, $type)
    {
        ResponseService::noPermissionThenSendJson('academy-setup-edit');

        if (!array_key_exists($type, $this->models)) {
            ResponseService::errorResponse('Invalid type');
        }

        try {
            $modelClass = $this->models[$type];
            $ids = $request->ids;

            foreach ($ids as $index => $id) {
                $modelClass::where('id', $id)->update(['sort_order' => $index + 1]);
            }

            ResponseService::successResponse('Order Updated Successfully');
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e->getMessage());
            ResponseService::errorResponse('something_went_wrong');
        }
    }
}
