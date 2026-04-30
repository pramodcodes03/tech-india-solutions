<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Expense Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { padding: 16px; background: {{ $stage === 'overdue' ? '#dc2626' : ($stage === 'due' ? '#f59e0b' : '#3b82f6') }}; color: #fff; border-radius: 6px 6px 0 0; }
        .header h1 { margin: 0; font-size: 18px; }
        .body { padding: 20px; background: #fff; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 6px 6px; }
        .meta { background: #f9fafb; padding: 12px; border-radius: 4px; margin: 16px 0; }
        .meta dt { font-weight: bold; display: inline-block; width: 130px; color: #6b7280; }
        .meta dd { display: inline; margin: 0; }
        .meta div { margin: 4px 0; }
        .amount { font-size: 24px; font-weight: bold; color: #111; }
        .footer { padding: 12px 0; text-align: center; color: #9ca3af; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 4px; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            @switch($stage)
                @case('t-3') Expense due in 3 days @break
                @case('t-1') Expense due tomorrow @break
                @case('due') Expense due today @break
                @case('overdue') OVERDUE — {{ $daysFromDue }} day(s) past due @break
                @default Expense reminder
            @endswitch
        </h1>
    </div>

    <div class="body">
        <p>Hi,</p>
        <p>This is a reminder for an upcoming/overdue expense at <strong>{{ $business->name }}</strong>.</p>

        <div class="meta">
            <div><dt>Expense</dt><dd>{{ $expense->title }}</dd></div>
            <div><dt>Code</dt><dd>{{ $expense->expense_code }}</dd></div>
            <div><dt>Category</dt><dd>{{ $expense->category->name ?? '—' }}{{ $expense->subcategory ? ' → '.$expense->subcategory->name : '' }}</dd></div>
            <div><dt>Type</dt><dd>{{ $expense->type === 'recurring' ? 'Monthly Recurring' : 'One-off' }}</dd></div>
            <div><dt>Due Date</dt><dd>{{ $expense->due_date?->format('d M Y') }}</dd></div>
        </div>

        <p>Amount due: <span class="amount">{{ $business->currency_symbol }}{{ number_format($expense->amount, 2) }}</span></p>

        @if($stage === 'overdue')
            <p style="color: #dc2626;"><strong>This expense is now overdue.</strong> Please action it as soon as possible.</p>
        @elseif($stage === 'due')
            <p style="color: #f59e0b;"><strong>This expense is due today.</strong></p>
        @endif

        <p>Once paid, log into the ERP and mark this expense as paid to stop further reminders.</p>

        <div class="footer">
            <p>This is an automated reminder from {{ $business->name }} ERP. You are receiving it because you are an admin of this business.</p>
        </div>
    </div>
</body>
</html>
