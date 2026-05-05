<x-layout.admin title="Create Payment Category">
    <x-admin.breadcrumb :items="[['label' => 'Routine Payment Tracker', 'url' => route('admin.expenses.index')], ['label' => 'Categories', 'url' => route('admin.expense-categories.index')], ['label' => 'Create']]" />

    <h5 class="text-lg font-semibold mb-4">Create Payment Category</h5>
    <form method="POST" action="{{ route('admin.expense-categories.store') }}">
        @csrf
        <div class="panel">@include('admin.expense-categories._form')</div>
        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create</button>
        </div>
    </form>
</x-layout.admin>
