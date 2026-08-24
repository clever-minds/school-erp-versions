<?php

namespace App\Http\Controllers;

use App\Models\AuditOptionGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuditOptionGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Auth::user()->can('audit-option-group-list')) {
            abort(403);
        }
        $optionGroups = AuditOptionGroup::orderBy('id', 'DESC')->get();
        return view('audit-option-groups.index', compact('optionGroups'));
    }

    public function create()
    {
        if (!Auth::user()->can('audit-option-group-create')) {
            abort(403);
        }
        return view('audit-option-groups.create');
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('audit-option-group-create')) {
            abort(403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'option_values' => 'required|string',
        ]);

        $optionValues = json_decode($request->option_values, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('error', 'Invalid JSON format for options.');
        }

        AuditOptionGroup::create([
            'name' => $request->name,
            'option_values' => $optionValues
        ]);

        return redirect()->route('audit-option-groups.index')->with('success', 'Option Group created successfully');
    }

    public function show(AuditOptionGroup $auditOptionGroup)
    {
        //
    }

    public function edit(AuditOptionGroup $auditOptionGroup)
    {
        if (!Auth::user()->can('audit-option-group-edit')) {
            abort(403);
        }
        return view('audit-option-groups.edit', compact('auditOptionGroup'));
    }

    public function update(Request $request, AuditOptionGroup $auditOptionGroup)
    {
        if (!Auth::user()->can('audit-option-group-edit')) {
            abort(403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'option_values' => 'required|string',
        ]);

        $optionValues = json_decode($request->option_values, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('error', 'Invalid JSON format for options.');
        }

        $auditOptionGroup->update([
            'name' => $request->name,
            'option_values' => $optionValues
        ]);

        return redirect()->route('audit-option-groups.index')->with('success', 'Option Group updated successfully');
    }

    public function destroy(AuditOptionGroup $auditOptionGroup)
    {
        if (!Auth::user()->can('audit-option-group-delete')) {
            abort(403);
        }
        $auditOptionGroup->delete();
        return redirect()->route('audit-option-groups.index')->with('success', 'Option Group deleted successfully');
    }
}
