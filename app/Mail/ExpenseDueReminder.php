<?php

namespace App\Mail;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpenseDueReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $stage  one of 't-3', 't-1', 'due', 'overdue'
     * @param  int  $daysFromDue  signed: -3 = 3 days before, 0 = due today, +N = N days overdue
     */
    public function __construct(
        public Expense $expense,
        public string $stage,
        public int $daysFromDue,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->expense->title;
        $code = $this->expense->expense_code;

        $subject = match ($this->stage) {
            't-3' => "Reminder: {$title} due in 3 days ({$code})",
            't-1' => "Reminder: {$title} due tomorrow ({$code})",
            'due' => "Due Today: {$title} ({$code})",
            'overdue' => "OVERDUE: {$title} — {$this->daysFromDue} day(s) past due ({$code})",
            default => "Expense reminder: {$title}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expense-due-reminder',
            with: [
                'expense' => $this->expense,
                'business' => $this->expense->business,
                'stage' => $this->stage,
                'daysFromDue' => $this->daysFromDue,
            ],
        );
    }
}
