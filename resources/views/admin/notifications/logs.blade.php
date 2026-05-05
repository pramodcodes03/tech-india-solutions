<x-layout.admin title="Email Send Log">
    <x-admin.breadcrumb :items="[['label'=>'Notifications','url'=>route('admin.notifications.index')],['label'=>'Send Log']]" />

    <div class="flex items-center justify-between mb-5">
        <h5 class="text-lg font-semibold">Email Send Log</h5>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">← Notification Settings</a>
    </div>

    <form method="GET" class="panel mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search recipient or subject…" class="form-input">
            <select name="status" class="form-select">
                <option value="">All status</option>
                <option value="queued" @selected(request('status') === 'queued')>Queued</option>
                <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
            <select name="event" class="form-select">
                <option value="">All events</option>
                @foreach($events as $e)
                    <option value="{{ $e['key'] }}" @selected(request('event') === $e['key'])>{{ $e['name'] }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </div>
    </form>

    <div class="panel px-0">
        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th class="px-4 py-2">When</th>
                        <th class="px-4 py-2">Event</th>
                        <th class="px-4 py-2">To</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $log->created_at->format('d-m-Y H:i') }}</td>
                            <td class="px-4 py-2"><code class="text-xs">{{ $log->event_key }}</code></td>
                            <td class="px-4 py-2 text-sm">
                                <div>{{ $log->recipient_email }}</div>
                                @if($log->recipient_name)<div class="text-xs text-gray-500">{{ $log->recipient_name }}</div>@endif
                            </td>
                            <td class="px-4 py-2 text-sm max-w-md truncate" title="{{ $log->subject }}">{{ $log->subject }}</td>
                            <td class="px-4 py-2">
                                @if($log->status === 'sent')
                                    <span class="badge bg-success">Sent</span>
                                @elseif($log->status === 'failed')
                                    <span class="badge bg-danger" title="{{ $log->error }}">Failed</span>
                                @else
                                    <span class="badge bg-warning">Queued</span>
                                @endif
                                @if($log->error)
                                    <div class="text-xs text-danger mt-1 max-w-xs truncate" title="{{ $log->error }}">{{ $log->error }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs">{{ $log->sent_at?->format('d-m-Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-8 text-gray-500">No emails logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-layout.admin>
