<x-layout.admin title="Edit Leave Type">
    <h1 class="text-2xl font-extrabold mb-4">Edit Leave Type</h1>
    <form method="POST" action="{{ route('admin.hr.leave-types.update', $leaveType) }}">
        @csrf @method('PUT')
        @include('admin.hr.leave-types._form', ['leaveType' => $leaveType])
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Save</button><a href="{{ route('admin.hr.leave-types.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
