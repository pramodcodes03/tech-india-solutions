<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentVerification;
use App\Notifications\NotificationDispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EmployeeDocumentService
{
    /**
     * Store an uploaded document. $by is 'admin' or 'employee'.
     */
    public function upload(Employee $employee, UploadedFile $file, array $meta, string $by = 'admin'): EmployeeDocument
    {
        $path = $file->store("employee-documents/{$employee->id}", 'public');

        $doc = EmployeeDocument::create([
            'business_id' => $employee->business_id,
            'employee_id' => $employee->id,
            'doc_type' => $meta['doc_type'],
            'title' => $meta['title'],
            'file_path' => $path,
            'file_mime' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'expires_on' => $meta['expires_on'] ?? null,
            'uploaded_by' => $by === 'admin' ? Auth::guard('admin')->id() : null,
            'employee_uploaded_by' => $by === 'employee' ? $employee->id : null,
            'verification_status' => 'pending',
        ]);

        $this->log($doc, 'uploaded', null, $by, $employee);

        // Notify admin/HR when an employee self-uploads a document.
        if ($by === 'employee') {
            NotificationDispatcher::fire('document.uploaded', $doc);
        }

        return $doc;
    }

    public function verify(EmployeeDocument $doc, ?string $remarks = null): EmployeeDocument
    {
        $doc->update([
            'verification_status' => 'verified',
            'verified_by' => Auth::guard('admin')->id(),
            'verified_at' => now(),
            'verification_remarks' => $remarks,
        ]);
        $this->log($doc, 'verified', $remarks, 'admin');
        NotificationDispatcher::fire('document.verified', $doc);

        return $doc;
    }

    public function reject(EmployeeDocument $doc, string $remarks): EmployeeDocument
    {
        $doc->update([
            'verification_status' => 'rejected',
            'verified_by' => Auth::guard('admin')->id(),
            'verified_at' => now(),
            'verification_remarks' => $remarks,
        ]);
        $this->log($doc, 'rejected', $remarks, 'admin');
        NotificationDispatcher::fire('document.rejected', $doc);

        return $doc;
    }

    public function delete(EmployeeDocument $doc): void
    {
        if ($doc->file_path) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
    }

    /**
     * Build a ZIP of all of an employee's documents and return the temp path.
     */
    public function bulkZip(Employee $employee): ?string
    {
        $docs = $employee->documents()->whereNotNull('file_path')->get();
        if ($docs->isEmpty()) {
            return null;
        }

        $tmp = storage_path('app/tmp');
        if (! is_dir($tmp)) {
            mkdir($tmp, 0775, true);
        }
        $zipPath = $tmp.'/documents-'.$employee->employee_code.'-'.now()->format('Ymd_His').'.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        foreach ($docs as $doc) {
            $abs = Storage::disk('public')->path($doc->file_path);
            if (is_file($abs)) {
                $ext = pathinfo($abs, PATHINFO_EXTENSION);
                $name = $doc->doc_type_label.'-'.$doc->id.($ext ? '.'.$ext : '');
                $zip->addFile($abs, $name);
            }
        }
        $zip->close();

        return $zipPath;
    }

    private function log(EmployeeDocument $doc, string $action, ?string $remarks, string $by, ?Employee $employee = null): void
    {
        EmployeeDocumentVerification::create([
            'business_id' => $doc->business_id,
            'employee_document_id' => $doc->id,
            'action' => $action,
            'remarks' => $remarks,
            'admin_id' => $by === 'admin' ? Auth::guard('admin')->id() : null,
            'employee_id' => $by === 'employee' ? ($employee?->id ?? $doc->employee_id) : null,
        ]);
    }
}
