<x-layout.employee title="Leave Policy">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Company Leave Policy</h1>
        <a href="{{ route('employee.leaves.index') }}" class="btn btn-outline-secondary">Back to Leaves</a>
    </div>

    <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        @if(trim($policy))
            <div class="prose dark:prose-invert max-w-none whitespace-pre-line">{{ $policy }}</div>
        @else
            <p class="text-gray-400">The leave policy has not been published yet. Please check with HR.</p>
        @endif
    </div>
</x-layout.employee>
