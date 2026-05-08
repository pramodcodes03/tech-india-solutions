<x-layout.admin title="Add Holiday">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Holidays', 'url' => route('admin.hr.holidays.index')], ['label' => 'Add']]" />
    <h1 class="text-2xl font-extrabold mb-5">Add Holiday</h1>
    <form method="POST" action="{{ route('admin.hr.holidays.store') }}">
        @csrf
        @include('admin.hr.holidays._form', ['employees' => $employees])
        <div class="flex gap-3 mt-5">
            <button class="btn btn-primary">Save Holiday</button>
            <a href="{{ route('admin.hr.holidays.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
