<x-layout.admin title="Edit Expense Category">
    <x-admin.breadcrumb :items="[['label' => 'Expenses', 'url' => route('admin.expenses.index')], ['label' => 'Categories', 'url' => route('admin.expense-categories.index')], ['label' => $category->name]]" />

    <h5 class="text-lg font-semibold mb-4">Edit: {{ $category->name }}</h5>
    <form method="POST" action="{{ route('admin.expense-categories.update', $category) }}">
        @csrf @method('PUT')
        <div class="panel">@include('admin.expense-categories._form', ['category' => $category])</div>
        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('admin.expense-categories.show', $category) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</x-layout.admin>
