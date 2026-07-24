<x-layout.employee title="My Referrals">
    <h1 class="text-2xl font-extrabold mb-4">My Referrals</h1>
    <p class="text-sm text-gray-500 mb-4">Candidates you referred and their current hiring status.</p>

    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <div class="overflow-x-auto">
            <table class="table table-striped w-full">
                <thead><tr><th>Candidate</th><th>Role</th><th>Stage</th><th>Status</th><th>Referred On</th></tr></thead>
                <tbody>
                    @forelse($referrals as $c)
                        @php $sc = ['active'=>'warning','hired'=>'success','rejected'=>'danger','withdrawn'=>'secondary'][$c->status] ?? 'secondary'; @endphp
                        <tr>
                            <td class="font-semibold">{{ $c->full_name }}</td>
                            <td>{{ $c->designation?->name ?? '—' }}</td>
                            <td>
                                @if($c->stage)<span class="badge" style="background: {{ $c->stage->color }}1a; color: {{ $c->stage->color }};">{{ $c->stage->name }}</span>@else — @endif
                            </td>
                            <td><span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($c->status) }}</span></td>
                            <td class="text-sm text-gray-500">{{ optional($c->applied_at)->format('d M Y') ?? $c->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-400 py-8">You haven't referred any candidates yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $referrals->links() }}</div>
    </div>
</x-layout.employee>
