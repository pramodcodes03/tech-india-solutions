<x-layout.admin title="Holidays">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Holidays']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Holiday Calendar</h1>
        @can('holidays.create')<a href="{{ route('admin.hr.holidays.create') }}" class="btn btn-primary">+ Add Holiday</a>@endcan
    </div>
    <form method="GET" class="flex gap-2 mb-4">
        <select name="year" class="form-select max-w-xs">
            @foreach(\App\Support\HrYears::forHolidays() as $y)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Show</button>
    </form>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Date</th><th>Day</th><th>Holiday</th><th>Type</th><th>Description</th><th></th></tr></thead>
            <tbody>
                @forelse($holidays as $h)
                    <tr>
                        <td class="font-semibold">{{ $h->date->format('d M Y') }}</td>
                        <td>{{ $h->date->format('l') }}</td>
                        <td>{{ $h->name }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold','bg-primary/10 text-primary' => $h->type === 'public','bg-warning/10 text-warning' => $h->type === 'optional','bg-info/10 text-info' => $h->type === 'restricted'])>{{ ucfirst($h->type) }}</span></td>
                        <td>{{ $h->description }}</td>
                        <td class="text-right">
                            @can('holidays.edit')<a href="{{ route('admin.hr.holidays.edit', $h) }}" class="text-info text-xs">Edit</a>@endcan
                            @can('holidays.delete')<form method="POST" action="{{ route('admin.hr.holidays.destroy', $h) }}" class="inline" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Remove</button></form>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500 py-6">No holidays for {{ $year }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
