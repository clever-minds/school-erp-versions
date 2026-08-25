<?php

namespace App\Http\Controllers;

use App\Repositories\Faqs\FaqsInterface;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class FaqController extends Controller
{
    private FaqsInterface $faqs;
    

    public function __construct(FaqsInterface $faqs)
    {
        $this->faqs = $faqs;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        ResponseService::noAnyPermissionThenRedirect(['faqs-list','faqs-create']);

        return view('faqs.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        ResponseService::noPermissionThenRedirect('faqs-create');
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description'  => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }
        try {
            $this->faqs->create($request->all());
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Faq Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        ResponseService::noPermissionThenRedirect('faqs-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = $this->faqs->builder()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orwhere('title', 'LIKE', "%$search%")->orwhere('description', 'LIKE', "%$search%")->orwhere('date', 'LIKE', "%$search%");
                });
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = BootstrapTableService::editButton(route('faqs.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('faqs.destroy', $row->id));
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        ResponseService::noPermissionThenSendJson('faqs-edit');
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description'  => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }
        try {
            $this->faqs->update($id,$request->all());
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Faq Controller -> Update Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        ResponseService::noPermissionThenSendJson('faqs-delete');
        try {
            $this->faqs->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Faq Controller -> Destroy Method");
            ResponseService::errorResponse();
        }
    }
}
