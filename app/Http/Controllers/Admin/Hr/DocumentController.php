<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
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

    public function index(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.view'), 403);
        $documents = $employee->documents()->with(['verifier', 'verifications.admin', 'verifications.employee'])->latest()->get();
        $docTypes = EmployeeDocument::DOC_TYPES;

        return view('admin.hr.documents.index', compact('employee', 'documents', 'docTypes'));
    }

    public function store(Request $request, Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.upload'), 403);
        $data = $request->validate([
            'doc_type' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:120'],
            'expires_on' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $this->service->upload($employee, $request->file('file'), $data, 'admin');

        return back()->with('success', 'Document uploaded.');
    }

    public function verify(Request $request, EmployeeDocument $document)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.verify'), 403);
        $request->validate(['remarks' => ['nullable', 'string', 'max:300']]);
        $this->service->verify($document, $request->remarks);

        return back()->with('success', 'Document marked as verified.');
    }

    public function reject(Request $request, EmployeeDocument $document)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.verify'), 403);
        $request->validate(['remarks' => ['required', 'string', 'max:300']]);
        $this->service->reject($document, $request->remarks);

        return back()->with('success', 'Document marked as rejected.');
    }

    public function destroy(EmployeeDocument $document)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.delete'), 403);
        $employee = $document->employee;
        $this->service->delete($document);

        return redirect()->route('admin.hr.employees.documents.index', $employee)->with('success', 'Document deleted.');
    }

    public function download(EmployeeDocument $document)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.view'), 403);
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        // Keep the real extension so the file opens in the right app.
        return Storage::disk('public')->download($document->file_path, $document->download_name);
    }

    /**
     * Open the document inline (PDF / image preview) in the browser.
     */
    public function view(EmployeeDocument $document)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.view'), 403);
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return response()->file(Storage::disk('public')->path($document->file_path));
    }

    /**
     * Download all of an employee's documents as a single ZIP.
     */
    public function bulkDownload(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employee_documents.view'), 403);
        $zipPath = $this->service->bulkZip($employee);
        abort_if($zipPath === null, 404, 'No documents to download.');

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
