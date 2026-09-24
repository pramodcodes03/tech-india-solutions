<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InternalTicket;
use App\Services\Documents\DocumentDataResolver;
use App\Support\DocumentCatalog;
use App\Support\Tenancy\CurrentBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The Documents hub: every generated document — the 42 of Module D and the
 * ten statutory registers of Module E — behind one screen and one route.
 *
 * Every document is declared in DocumentCatalog and rendered through the shared
 * letterhead layout, so a new document is a catalogue row plus a Blade view —
 * no new controller, route or permission.
 */
class DocumentController extends Controller
{
    public function __construct(private DocumentDataResolver $resolver) {}

    /** The hub — packs, documents, and the picker for whichever is selected. */
    public function index(Request $request)
    {
        abort_unless($this->canOpenHub(), 403);

        $key = $request->input('document');
        $document = $key ? DocumentCatalog::find($key) : null;

        // A document the viewer cannot generate is treated as not chosen.
        if ($document && ! $this->canReach($key)) {
            $document = null;
            $key = null;
        }

        return view('admin.documents.index', [
            'packs' => DocumentCatalog::PACKS,
            'catalog' => DocumentCatalog::all(),
            'visiblePacks' => $this->visiblePacks(),
            'reachableKeys' => $this->reachableKeys(),
            'key' => $key,
            'document' => $document,
            'options' => $document && ($document['scope'] ?? '') === 'record'
                ? $this->resolver->optionsFor($document['subject'], $request->input('q'))
                : collect(),
            'departments' => Department::orderBy('name')->get(),
            'business' => app(CurrentBusiness::class)->get(),
        ]);
    }

    /** Render one document as a PDF. */
    public function render(Request $request, string $key)
    {
        $document = DocumentCatalog::find($key);
        abort_if($document === null, 404);

        abort_unless($this->canReach($key, 'generate'), 403);

        $data = $request->validate([
            'record' => ['nullable', 'integer'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'date' => ['nullable', 'date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'department_id' => ['nullable', 'exists:departments,id'],
            // Helpdesk tickets carry their own department enum, separate from
            // the employee Department table above.
            'ticket_department' => ['nullable', Rule::in(array_keys(InternalTicket::DEPARTMENTS))],
            'format' => ['nullable', Rule::in(['pdf', 'xlsx'])],
            'watermark' => ['nullable', Rule::in(['', 'DRAFT', 'PAID', 'CANCELLED', 'DUPLICATE'])],
            'stream' => ['nullable', 'boolean'],
        ]);

        $record = null;
        if (($document['scope'] ?? '') === 'record') {
            if (empty($data['record'])) {
                return back()->with('error', 'Choose a record before generating this document.');
            }

            $modelClass = $this->resolver->modelFor($document['subject']);
            abort_if($modelClass === null, 404);

            // Tenant-scoped find — a record from another business simply is not
            // there, so there is nothing to leak.
            $record = $modelClass::find($data['record']);
            if (! $record) {
                return back()->with('error', 'That record could not be found in the current business.');
            }
        }

        // Downloading a document takes the data out of the system, so a
        // document may require a further right on top of being able to view it.
        if (! empty($document['export_permission'])) {
            abort_unless(
                Auth::guard('admin')->user()->can($document['export_permission']),
                403,
            );
        }

        $payload = $this->resolver->resolve($key, $record, $data);

        // A table-shaped document already carries headings and rows, so a
        // spreadsheet is the same data without the letterhead around it.
        if (($data['format'] ?? 'pdf') === 'xlsx') {
            abort_unless(! empty($document['excel']), 404);

            return Excel::download(
                new GenericArrayExport($payload['headings'] ?? [], $payload['rows'] ?? []),
                str($document['name'])->slug().'-'.now()->format('Y-m-d').'.xlsx',
            );
        }

        $pdf = Pdf::loadView(DocumentCatalog::templateFor($key), array_merge($payload, [
            'business' => app(CurrentBusiness::class)->get(),
            'watermark' => $data['watermark'] ?? ($document['watermark'] ?? null),
            'generatedAt' => now()->format('d M Y, h:i A'),
            'documentName' => $document['name'],
            'documentKey' => $key,
            // The layout needs this too: DOMPDF lets the @page `size` override
            // setPaper(), so a landscape document that only calls setPaper()
            // still prints portrait and clips its right-hand columns.
            'landscape' => ! empty($document['landscape']),
        ]))->setPaper('a4', ! empty($document['landscape']) ? 'landscape' : 'portrait');

        $filename = $this->filename($document, $record, $data);

        return $request->boolean('stream')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    // ── Letterhead configuration ─────────────────────────────────────────

    public function letterhead()
    {
        abort_unless(Auth::guard('admin')->user()->can('documents.configure'), 403);

        return view('admin.documents.letterhead', [
            'business' => app(CurrentBusiness::class)->get(),
        ]);
    }

    /** Save the letterhead: signature, seal, signatory and footer line. */
    public function saveLetterhead(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('documents.configure'), 403);

        $business = app(CurrentBusiness::class)->get();
        abort_if($business === null, 404);

        $data = $request->validate([
            'signatory_name' => ['nullable', 'string', 'max:120'],
            'signatory_role' => ['nullable', 'string', 'max:120'],
            'letterhead_footer' => ['nullable', 'string', 'max:500'],
            'letterhead_enabled' => ['nullable', 'boolean'],
            'signature' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:1024'],
            'seal' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:1024'],
            'remove_signature' => ['nullable', 'boolean'],
            'remove_seal' => ['nullable', 'boolean'],
        ], [
            'signature.mimes' => 'The signature must be a PNG or JPG — a transparent PNG prints best.',
            'seal.mimes' => 'The seal must be a PNG or JPG — a transparent PNG prints best.',
            'signature.max' => 'The signature image may not be larger than 1 MB.',
            'seal.max' => 'The seal image may not be larger than 1 MB.',
        ]);

        $update = [
            'signatory_name' => $data['signatory_name'] ?? null,
            'signatory_role' => $data['signatory_role'] ?? null,
            'letterhead_footer' => $data['letterhead_footer'] ?? null,
            // A checkbox that is off posts nothing, so read it explicitly.
            'letterhead_enabled' => $request->boolean('letterhead_enabled'),
        ];

        foreach (['signature' => 'signature_path', 'seal' => 'seal_path'] as $field => $column) {
            if ($request->hasFile($field)) {
                if ($business->{$column}) {
                    Storage::disk('public')->delete($business->{$column});
                }
                $update[$column] = $request->file($field)->store('letterhead', 'public');
            } elseif ($request->boolean("remove_{$field}") && $business->{$column}) {
                Storage::disk('public')->delete($business->{$column});
                $update[$column] = null;
            }
        }

        $business->update($update);

        return back()->with('success', 'Letterhead saved. Every document now prints with these settings.');
    }

    // ── Internals ────────────────────────────────────────────────────────

    /** Can the viewer see (or generate) documents in this document's pack? */
    private function canAccess(string $key, string $action = 'view'): bool
    {
        $module = DocumentCatalog::permissionFor($key);

        return $module !== null
            && Auth::guard('admin')->user()->can("{$module}.{$action}");
    }

    /**
     * A document that declares its own `permission` in the catalogue is
     * governed by that module ALONE — that is the whole point of declaring
     * one. The Helpdesk Report is the case in hand: a helpdesk lead holds
     * helpdesk_reports.* and nothing else, so requiring the hub-wide
     * 'documents.view' on top of it made the report unreachable for exactly
     * the role it was carved out for.
     *
     * Documents without their own module keep the old rule: the hub-wide
     * permission plus their pack's.
     */
    private function canReach(string $key, string $action = 'view'): bool
    {
        if (! $this->canAccess($key, $action)) {
            return false;
        }

        return isset(DocumentCatalog::find($key)['permission'])
            || Auth::guard('admin')->user()->can('documents.view');
    }

    /** Every document key this viewer can reach, for the given action. */
    private function reachableKeys(string $action = 'view'): array
    {
        return array_keys(array_filter(
            DocumentCatalog::all(),
            fn ($doc, $key) => $this->canReach($key, $action),
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /** The hub opens for anyone who can reach at least one document. */
    private function canOpenHub(): bool
    {
        return Auth::guard('admin')->user()->can('documents.view')
            || $this->reachableKeys() !== [];
    }

    /** @return array<string, array{0:string,1:string}> packs the viewer can see */
    private function visiblePacks(): array
    {
        $user = Auth::guard('admin')->user();
        $reachable = $this->reachableKeys();

        return array_filter(
            DocumentCatalog::PACKS,
            function ($pack, $packKey) use ($user, $reachable) {
                if ($user->can($pack[1].'.view')) {
                    return true;
                }

                // Otherwise the pack still shows if it carries a document the
                // viewer can reach on that document's own permission — the
                // pack then lists only that document (see the hub view).
                return array_intersect(
                    $reachable,
                    array_keys(DocumentCatalog::forPack($packKey)),
                ) !== [];
            },
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** A filename that says what the document is without opening it. */
    private function filename(array $document, mixed $record, array $data): string
    {
        $parts = [str($document['name'])->slug()];

        if ($record) {
            $label = $record->employee_code
                ?? $record->invoice_number
                ?? $record->order_number
                ?? $record->code
                ?? $record->id;
            $parts[] = str((string) $label)->slug();
        } elseif (! empty($data['month']) || ! empty($data['year'])) {
            $parts[] = sprintf('%04d-%02d', $data['year'] ?? now()->year, $data['month'] ?? now()->month);
        } elseif (! empty($data['date'])) {
            $parts[] = $data['date'];
        }

        return implode('-', array_filter($parts)).'.pdf';
    }
}
