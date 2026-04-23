<x-layout.admin title="Edit Holiday">
    <h1 class="text-2xl font-extrabold mb-4">Edit Holiday</h1>
    <form method="POST" action="{{ route('admin.hr.holidays.update', $holiday) }}">
        @csrf @method('PUT')
        @include('admin.hr.holidays._form', ['holiday' => $holiday])
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Save</button><a href="{{ route('admin.hr.holidays.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
