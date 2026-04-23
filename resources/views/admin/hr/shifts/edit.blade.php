<x-layout.admin title="Edit Shift">
    <h1 class="text-2xl font-extrabold mb-4">Edit Shift</h1>
    <form method="POST" action="{{ route('admin.hr.shifts.update', $shift) }}">
        @csrf @method('PUT')
        @include('admin.hr.shifts._form', ['shift' => $shift])
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Save</button><a href="{{ route('admin.hr.shifts.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
