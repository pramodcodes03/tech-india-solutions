<?php

namespace App\Services;

use App\Models\ServiceTicket;
use App\Models\ServiceTicketComment;
use Illuminate\Support\Facades\Auth;

class ServiceTicketService
{
    /**
     * Generate the next service ticket number in SRV-YYYY-0001 format.
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "SRV-{$year}-";
        $last = ServiceTicket::withTrashed()
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('ticket_number')
            ->first();

        $nextNumber = $last ? (int) substr($last->ticket_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new service ticket.
     */
    public function create(array $data): ServiceTicket
    {
        $data['ticket_number'] = $this->generateNumber();
        $data['created_by'] = Auth::guard('admin')->id();
        $data['opened_at'] = $data['opened_at'] ?? now();

        return ServiceTicket::create($data);
    }

    /**
     * Update a service ticket. If status changes to "closed", set closed_at.
     */
    public function update(ServiceTicket $ticket, array $data): ServiceTicket
    {
        $data['updated_by'] = Auth::guard('admin')->id();

        // If status is being changed to "closed", set the closed_at timestamp
        if (isset($data['status']) && $data['status'] === 'closed' && $ticket->status !== 'closed') {
            $data['closed_at'] = now();
        }

        $ticket->update($data);

        return $ticket->refresh();
    }

    /**
     * Soft-delete a service ticket.
     */
    public function delete(ServiceTicket $ticket): void
    {
        $ticket->update(['deleted_by' => Auth::guard('admin')->id()]);
        $ticket->delete();
    }

    /**
     * Add a comment to a service ticket.
     */
    public function addComment(ServiceTicket $ticket, string $comment, int $adminId): ServiceTicketComment
    {
        return ServiceTicketComment::create([
            'service_ticket_id' => $ticket->id,
            'comment' => $comment,
            'created_by' => $adminId,
        ]);
    }
}
