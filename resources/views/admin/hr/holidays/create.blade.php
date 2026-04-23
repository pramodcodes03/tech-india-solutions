<x-layout.admin title="Add Holiday">
    <h1 class="text-2xl font-extrabold mb-4">Add Holiday</h1>
    <form method="POST" action="{{ route('admin.hr.holidays.store') }}">
        @csrf
        @include('admin.hr.holidays._form')
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Add</button><a href="{{ route('admin.hr.holidays.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
