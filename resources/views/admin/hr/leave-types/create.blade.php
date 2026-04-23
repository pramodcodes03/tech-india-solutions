<x-layout.admin title="New Leave Type">
    <h1 class="text-2xl font-extrabold mb-4">New Leave Type</h1>
    <form method="POST" action="{{ route('admin.hr.leave-types.store') }}">
        @csrf
        @include('admin.hr.leave-types._form')
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Create</button><a href="{{ route('admin.hr.leave-types.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
