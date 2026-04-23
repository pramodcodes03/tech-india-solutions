<x-layout.admin title="Feedback">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Feedback for {{ $feedback->department->name }}</h1>
        <a href="{{ route('admin.hr.feedback.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>
    <div class="panel p-6 max-w-2xl">
        <div class="text-warning text-2xl mb-3">{!! str_repeat('★', $feedback->rating) . str_repeat('☆', 5 - $feedback->rating) !!}</div>
        <div class="whitespace-pre-wrap mb-4">{{ $feedback->feedback }}</div>
        <div class="text-xs text-gray-500 border-t border-gray-200 dark:border-gray-700 pt-3">
            @if($feedback->is_anonymous)
                <em>Submitted anonymously</em>
            @else
                by {{ $feedback->employee->full_name }} ({{ $feedback->employee->employee_code }})
            @endif
            · {{ $feedback->created_at->format('d M Y, g:i A') }}
        </div>
    </div>
</x-layout.admin>
