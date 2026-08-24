<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Gallery\GalleryInterface;
use App\Repositories\Files\FilesInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Rules\MaxFileSize;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StaffGalleryApiController extends Controller
{
    private GalleryInterface $gallery;
    private FilesInterface $files;
    private SessionYearInterface $sessionYear;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(
        GalleryInterface $gallery,
        FilesInterface $files,
        SessionYearInterface $sessionYear,
        CachingService $cache,
        SessionYearsTrackingsService $sessionYearsTrackingsService
    ) {
        $this->gallery = $gallery;
        $this->files = $files;
        $this->sessionYear = $sessionYear;
        $this->cache = $cache;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function index()
    {
        $request = request();
        ResponseService::noPermissionThenSendJson('gallery-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        try {
            $sql = $this->gallery->builder()->with('file', 'session_years_trackings')
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where('title', 'LIKE', "%$search%")
                            ->orWhere('description', 'LIKE', "%$search%");
                    });
                })
                ->when(request('session_year_id') != null, function ($query) use ($request) {
                    $query->where('session_year_id', $request->session_year_id);
                });

            if (request('semester_id') != null) {
                $semester_id = request('semester_id');
                $sql = $sql->whereHas('session_years_trackings', function ($q) use ($semester_id) {
                    $q->where('semester_id', $semester_id);
                });
            }

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [
                'total' => $total,
                'data' => $res
            ];
            
            return ResponseService::successResponse('Gallery fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffGalleryApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('gallery-create');
        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit') ?? 20;
        
        $validator = Validator::make($request->all(), [
            'title'       => 'required',
            'description' => 'required',
            'image'       => 'nullable|array',
            'image.*'     => ['mimes:jpeg,png,jpg,gif,svg,webp', new MaxFileSize($file_upload_size_limit)],
            'youtube_url' => 'nullable|array',
            'thumbnail.*' => ['nullable', 'mimes:jpeg,png,jpg,gif,svg,webp', new MaxFileSize($file_upload_size_limit)],
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $sessionYear = $this->cache->getDefaultSessionYear();
            
            $galleryData = array(
                'title'           => $request->title,
                'description'     => $request->description,
                'session_year_id' => $sessionYear->id,
            );
            $gallery = $this->gallery->create($galleryData);

            $semester = $this->cache->getDefaultSemesterData();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Gallery', $gallery->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);

            if ($request->hasFile('image')) {
                $fileData = array();
                $fileInstance = $this->files->model();
                $galleryModelAssociate = $fileInstance->modal()->associate($gallery);
                
                foreach ($request->file('image') as $file_upload) {
                    $tempFileData = array(
                        'modal_type' => $galleryModelAssociate->modal_type,
                        'modal_id'   => $galleryModelAssociate->modal_id,
                        'file_name'  => $file_upload->getClientOriginalName(),
                        'type'       => 1,
                        'file_url'   => $file_upload
                    );
                    $fileData[] = $tempFileData;
                }
                $this->files->createBulk($fileData);
            }

            if ($request->youtube_url) {
                $urlData = array();
                foreach ($request->youtube_url as $key => $url) {
                    if (!empty($url)) {
                        $fileInstance = $this->files->model();
                        $galleryModelAssociate = $fileInstance->modal()->associate($gallery);

                        $thumbnail_url = null;
                        if ($request->hasFile("thumbnail.$key")) {
                            $thumbnail = $request->file("thumbnail.$key");
                            $thumbnail_url = Storage::disk('public')->putFile('gallery', $thumbnail);
                        }

                        $tempUrlData = array(
                            'modal_type' => $galleryModelAssociate->modal_type,
                            'modal_id'   => $galleryModelAssociate->modal_id,
                            'file_name'  => 'youtube_link_' . ($key + 1),
                            'type'       => 2,
                            'file_thumbnail' => $thumbnail_url,
                            'file_url'   => $url,
                        );
                        $urlData[] = $tempUrlData;
                    }
                }
                if (count($urlData) > 0) {
                    $this->files->createBulk($urlData);
                }
            }
            
            DB::commit();
            return ResponseService::successResponse('Gallery Created Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffGalleryApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('gallery-delete');
        try {
            DB::beginTransaction();
            $gallery = $this->gallery->findById($id);

            if ($gallery->file) {
                foreach ($gallery->file as $file) {
                    if ($file->type == 1 && Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                    if ($file->type == 2 && !empty($file->getRawOriginal('file_thumbnail')) && Storage::disk('public')->exists($file->getRawOriginal('file_thumbnail'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_thumbnail'));
                    }
                }
                $gallery->file()->delete();
            }

            $this->gallery->deleteById($id);

            DB::commit();
            return ResponseService::successResponse('Gallery Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffGalleryApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
