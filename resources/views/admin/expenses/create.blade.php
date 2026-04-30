<x-layout.admin title="Add Expense">
    <x-admin.breadcrumb :items="[['label' => 'Expenses', 'url' => route('admin.expenses.index')], ['label' => 'Add']]" />

    <h5 class="text-lg font-semibold mb-4">Add Expense</h5>
    <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel">@include('admin.expenses._form')</div>
        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Expense</button>
        </div>
    </form>
</x-layout.admin>
