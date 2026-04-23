<x-layout.employee title="Department Feedback">
    <h1 class="text-2xl font-extrabold mb-4">Department Feedback</h1>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-7 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Share your feedback</h3>
            <p class="text-sm text-gray-500 mb-4">Your feedback helps us improve how departments collaborate. You can choose to stay anonymous.</p>

            <form method="POST" action="{{ route('employee.feedback.store') }}" x-data="{ rating: 5, anon: false }" class="space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Department</label>
                    <select name="department_id" required class="form-select mt-1">
                        <option value="">-- Select a department --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Rating</label>
                    <div class="flex items-center gap-1 mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" @click="rating = {{ $i }}" class="text-2xl transition" :class="rating >= {{ $i }} ? 'text-warning' : 'text-gray-300'">★</button>
                        @endfor
                        <span class="ml-3 text-sm text-gray-500" x-text="rating + ' / 5'"></span>
                    </div>
                    <input type="hidden" name="rating" x-model="rating" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Feedback *</label>
                    <textarea name="feedback" rows="4" required minlength="10" class="form-input mt-1" placeholder="What's working well? What could improve?"></textarea>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_anonymous" value="1" x-model="anon" />
                    <span class="text-sm">Submit anonymously</span>
                </label>

                <button class="btn btn-primary">Submit Feedback</button>
            </form>
        </div>

        <div class="col-span-12 lg:col-span-5 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Your recent submissions</h3>
            @forelse($myFeedback as $f)
                <div class="py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold">{{ $f->department->name }}</div>
                        <div class="text-warning">{!! str_repeat('★', $f->rating) . str_repeat('☆', 5 - $f->rating) !!}</div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">{{ $f->feedback }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">
                        {{ $f->created_at->format('d M Y, g:i A') }}
                        @if($f->is_anonymous) · anonymous @endif
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 py-4 text-center">You haven't submitted any feedback yet.</div>
            @endforelse
        </div>
    </div>
</x-layout.employee>
