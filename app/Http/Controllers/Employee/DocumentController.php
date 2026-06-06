<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(private EmployeeDocumentService $service)
    {
    }

    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $documents = $employee->documents()->latest()->get();
        $docTypes = EmployeeDocument::DOC_TYPES;

        return view('employee.documents.index', compact('documents', 'docTypes'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $data = $request->validate([
            'doc_type' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:120'],
            'expires_on' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $this->service->upload($employee, $request->file('file'), $data, 'employee');

        return back()->with('success', 'Document uploaded. HR will verify it shortly.');
    }

    public function download(EmployeeDocument $document)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($document->employee_id === $employee->id, 403);
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path, $document->title);
    }

    public function destroy(EmployeeDocument $document)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($document->employee_id === $employee->id, 403);
        // Employees may only delete their own still-pending uploads.
        abort_unless($document->verification_status === 'pending' && $document->employee_uploaded_by === $employee->id, 403);
        $this->service->delete($document);

        return back()->with('success', 'Document removed.');
    }
}
