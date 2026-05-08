<x-layout.admin title="Edit Holiday">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Holidays', 'url' => route('admin.hr.holidays.index')], ['label' => 'Edit']]" />
    <h1 class="text-2xl font-extrabold mb-5">Edit Holiday</h1>
    <form method="POST" action="{{ route('admin.hr.holidays.update', $holiday) }}">
        @csrf @method('PUT')
        @include('admin.hr.holidays._form', ['holiday' => $holiday, 'employees' => $employees])
        <div class="flex gap-3 mt-5">
            <button class="btn btn-primary">Update Holiday</button>
            <a href="{{ route('admin.hr.holidays.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
