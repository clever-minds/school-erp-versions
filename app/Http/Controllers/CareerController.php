<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\TeacherInterviewApplication;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CareerController extends Controller
{
    public function index()
    {
        $schools = School::where('status', 1)->get();
        return view('careers.index', compact('schools'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|exists:schools,id',
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'phone'     => 'required|string|max:20',
            'resume'    => 'required|mimes:pdf,doc,docx|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $application = new TeacherInterviewApplication();
        $application->school_id = $request->school_id;
        $application->name = $request->name;
        $application->email = $request->email;
        $application->phone = $request->phone;
        $application->status = 'pending';

        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('teacher_resumes', $filename, 'public');
            $application->resume_path = $path;
        }

        $application->save();

        return redirect()->back()->with('success', 'Your application has been submitted successfully! We will contact you soon.');
    }
}
