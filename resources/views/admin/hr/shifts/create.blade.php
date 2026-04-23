<x-layout.admin title="New Shift">
    <h1 class="text-2xl font-extrabold mb-4">New Shift</h1>
    <form method="POST" action="{{ route('admin.hr.shifts.store') }}">
        @csrf
        @include('admin.hr.shifts._form')
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Create</button><a href="{{ route('admin.hr.shifts.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
