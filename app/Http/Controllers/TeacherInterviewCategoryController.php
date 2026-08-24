<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewCategoryController extends Controller
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

            $query = TeacherInterviewCategory::query();

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $query->orderBy($sort, $order)->skip($offset)->take($limit);
            $categories = $query->get();

            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            $no = 1;

            foreach ($categories as $category) {
                $operate = '<button class="btn btn-sm btn-info btn-rounded btn-icon edit-btn" data-id="' . $category->id . '" title="' . __('Edit') . '"><i class="fa fa-edit"></i></button>&nbsp;';
                
                $operate .= '<form action="' . route('teacher-interview-categories.destroy', $category->id) . '" method="POST" style="display:inline;" onsubmit="return confirm(\'' . __('Are you sure you want to delete this category?') . '\');">';
                $operate .= csrf_field();
                $operate .= method_field('DELETE');
                $operate .= '<button type="submit" class="btn btn-sm btn-danger btn-rounded btn-icon" title="' . __('Delete') . '"><i class="fa fa-trash"></i></button>';
                $operate .= '</form>';

                $statusBadge = $category->status == 1 ? '<span class="badge badge-success">' . __('Active') . '</span>' : '<span class="badge badge-danger">' . __('Inactive') . '</span>';

                $tempRow = $category->toArray();
                $tempRow['no'] = $no++;
                $tempRow['description'] = $category->description ?? '-';
                $tempRow['status_badge'] = $statusBadge;
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }

        $categories = TeacherInterviewCategory::orderBy('id', 'desc')->get(); // needed for modal forms if any
        return view('teacher-interview-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-question-create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:1,0',
        ]);

        TeacherInterviewCategory::create($request->all());

        return redirect()->route('teacher-interview-categories.index')->with('success', __('Category created successfully.'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-question-edit')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:1,0',
        ]);

        $category = TeacherInterviewCategory::findOrFail($id);
        $category->update($request->all());

        return redirect()->route('teacher-interview-categories.index')->with('success', __('Category updated successfully.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('teacher-interview-question-delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category = TeacherInterviewCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('teacher-interview-categories.index')->with('success', __('Category deleted successfully.'));
    }
}
