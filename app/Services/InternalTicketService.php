<?php

namespace App\Services;

use App\Models\InternalTicket;
use App\Models\InternalTicketCategory;
use App\Models\InternalTicketComment;
use App\Models\TicketEscalationLevel;
use App\Notifications\NotificationDispatcher;
use App\Support\HrSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class InternalTicketService
{
    public function nextNumber(int $businessId): string
    {
        $count = InternalTicket::where('business_id', $businessId)->count();

        return 'INT-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create an internal ticket, stamping the TAT from the category (or the
     * global escalation-days default) and notifying the department.
     */
    public function create(array $data, ?Model $related = null): InternalTicket
    {
        $businessId = $data['business_id'];
        $data['ticket_number'] = $this->nextNumber($businessId);
        $data['status'] = 'open';

        // TAT due: category tat_hours, else global ticket_escalation_days (in hours).
        // Also route the ticket to the category's designated handler (if the
        // category names a specific admin) so ONLY that user is notified.
        $tatHours = null;
        if (! empty($data['category_id'])) {
            $category = InternalTicketCategory::find($data['category_id']);
            $tatHours = $category?->tat_hours;

            // Direct the ticket to the category's assigned user when set.
            if ($category && $category->assigned_admin_id && empty($data['assigned_to'])) {
                $data['assigned_to'] = $category->assigned_admin_id;
                $data['status'] = 'assigned';
            }
        }
        $tatHours = $tatHours ?: (HrSettings::int('ticket_escalation_days', 3) * 24);
        $data['tat_due_at'] = now()->addHours($tatHours);

        if ($related) {
            $data['related_type'] = $related->getMorphClass();
            $data['related_id'] = $related->getKey();
        }

        $ticket = InternalTicket::create($data);

        NotificationDispatcher::fire('internal_ticket.created', $ticket);

        return $ticket;
    }

    public function assign(InternalTicket $ticket, int $adminId): InternalTicket
    {
        $ticket->update([
            'assigned_to' => $adminId,
            'status' => $ticket->status === 'open' ? 'assigned' : $ticket->status,
        ]);
        NotificationDispatcher::fire('internal_ticket.assigned', $ticket);

        return $ticket;
    }

    public function changeStatus(InternalTicket $ticket, string $status): InternalTicket
    {
        $ticket->status = $status;
        if ($status === 'resolved') {
            $ticket->resolved_at = now();
        }
        if ($status === 'closed') {
            $ticket->closed_at = now();
        }
        $ticket->save();

        \App\Models\AdminNotification::markRelatedAsRead($ticket, ['internal_ticket.created', 'internal_ticket.escalated']);

        NotificationDispatcher::fire(
            $status === 'closed' ? 'internal_ticket.closed' : 'internal_ticket.status_changed',
            $ticket,
            ['new_status' => InternalTicket::STATUSES[$status] ?? $status]
        );

        return $ticket;
    }

    public function comment(InternalTicket $ticket, string $body, string $by, bool $internalNote = false): InternalTicketComment
    {
        return InternalTicketComment::create([
            'business_id' => $ticket->business_id,
            'internal_ticket_id' => $ticket->id,
            'body' => $body,
            'author_admin_id' => $by === 'admin' ? Auth::guard('admin')->id() : null,
            'author_employee_id' => $by === 'employee' ? Auth::guard('employee')->id() : null,
            'is_internal_note' => $internalNote,
        ]);
    }

    /**
     * Escalate open tickets past their TAT or past a configured escalation-level
     * threshold. Run from the scheduler.
     *
     * @return int number escalated
     */
    public function escalateBreaches(): int
    {
        $escalated = 0;

        $tickets = InternalTicket::whereNotIn('status', ['resolved', 'closed'])->get();

        foreach ($tickets as $ticket) {
            // Mark TAT breach.
            if ($ticket->tat_due_at && $ticket->tat_due_at->isPast() && ! $ticket->tat_breached) {
                $ticket->tat_breached = true;
            }

            // Walk the escalation matrix for this department.
            $ageDays = $ticket->created_at->diffInDays(now());
            $nextLevel = TicketEscalationLevel::where('business_id', $ticket->business_id)
                ->where('department', $ticket->department)
                ->where('status', true)
                ->where('level', '>', $ticket->escalation_level)
                ->where('after_days', '<=', $ageDays)
                ->orderBy('level')
                ->first();

            if ($nextLevel) {
                $ticket->escalation_level = $nextLevel->level;
                $ticket->escalated_at = now();
                $ticket->save();
                NotificationDispatcher::fire('internal_ticket.escalated', $ticket, [
                    'level' => $nextLevel->level,
                    'owner' => $nextLevel->owner?->name,
                ]);
                $escalated++;
            } elseif ($ticket->isDirty()) {
                $ticket->save();
            }
        }

        return $escalated;
    }
}
