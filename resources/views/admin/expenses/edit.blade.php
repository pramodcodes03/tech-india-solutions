<x-layout.admin title="Edit Expense">
    <x-admin.breadcrumb :items="[['label' => 'Expenses', 'url' => route('admin.expenses.index')], ['label' => $expense->expense_code]]" />

    <h5 class="text-lg font-semibold mb-4">Edit: {{ $expense->expense_code }}</h5>
    <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="panel">@include('admin.expenses._form', ['expense' => $expense])</div>
        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('admin.expenses.show', $expense) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</x-layout.admin>
