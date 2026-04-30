<x-layout.admin title="Select a Business">
    <div class="max-w-3xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-2 dark:text-white-light">Select a business</h1>
        <p class="text-gray-500 mb-6">You're signed in as Super Admin. Pick a business to act on. You can switch any time from the header dropdown.</p>

        @if (session('error'))
            <div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">{{ session('error') }}</div>
        @endif

        @if($businesses->isEmpty())
            <div class="panel text-center py-10">
                <p class="text-gray-500 mb-4">No businesses exist yet.</p>
                <a href="{{ route('admin.businesses.create') }}" class="btn btn-primary">Create your first business</a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($businesses as $business)
                    <form method="POST" action="{{ route('admin.businesses.switch', $business) }}" class="panel hover:shadow-lg transition cursor-pointer">
                        @csrf
                        <button type="submit" class="w-full text-left flex items-center gap-3">
                            @if($business->logo)
                                <img src="{{ asset('storage/'.$business->logo) }}" class="w-14 h-14 rounded object-cover" />
                            @else
                                <div class="w-14 h-14 rounded bg-primary/10 text-primary flex items-center justify-center text-lg font-bold">{{ strtoupper(substr($business->name, 0, 2)) }}</div>
                            @endif
                            <div class="flex-1">
                                <div class="font-semibold">{{ $business->name }}</div>
                                <div class="text-xs text-gray-500">{{ $business->city }}{{ $business->city && $business->gst ? ' · ' : '' }}{{ $business->gst }}</div>
                            </div>
                            <span class="text-primary">→</span>
                        </button>
                    </form>
                @endforeach
            </div>

            <div class="text-center mt-6">
                <a href="{{ route('admin.businesses.index') }}" class="text-primary text-sm">Manage businesses →</a>
            </div>
        @endif
    </div>
</x-layout.admin>
