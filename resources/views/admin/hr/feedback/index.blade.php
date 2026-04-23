<x-layout.admin title="Department Feedback">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Feedback']]" />
    <h1 class="text-2xl font-extrabold mb-4">Department Feedback</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach($byDept as $d)
            <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="flex items-center justify-between mb-1">
                    <div class="font-bold">{{ $d->name }}</div>
                    <div class="text-warning">{!! str_repeat('★', (int) round($d->feedback_avg_rating ?? 0)) . str_repeat('☆', 5 - (int) round($d->feedback_avg_rating ?? 0)) !!}</div>
                </div>
                <div class="flex items-end justify-between">
                    <div class="text-xs text-gray-500">{{ $d->feedback_count }} feedback</div>
                    <div class="text-lg font-extrabold">{{ number_format($d->feedback_avg_rating ?? 0, 1) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="flex gap-2 mb-4">
        <select name="department_id" class="form-select max-w-xs">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <select name="rating" class="form-select max-w-xs">
            <option value="">All Ratings</option>
            @for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected(request('rating') == $i)>{{ $i }} ★ and higher</option>@endfor
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="space-y-3">
        @forelse($feedback as $f)
            <div class="panel p-5">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <div class="font-bold">{{ $f->department->name }}</div>
                        <div class="text-xs text-gray-500">
                            @if($f->is_anonymous)
                                <em>Anonymous</em>
                            @else
                                by {{ $f->employee->full_name }} ({{ $f->employee->employee_code }})
                            @endif
                            · {{ $f->created_at->format('d M Y, g:i A') }}
                        </div>
                    </div>
                    <div class="text-warning text-lg">{!! str_repeat('★', $f->rating) . str_repeat('☆', 5 - $f->rating) !!}</div>
                </div>
                <div class="text-sm whitespace-pre-wrap">{{ $f->feedback }}</div>
            </div>
        @empty
            <div class="panel p-8 text-center text-gray-500">No feedback yet.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $feedback->links() }}</div>
</x-layout.admin>
