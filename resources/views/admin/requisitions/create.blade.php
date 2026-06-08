<x-layout.admin title="New Requisition">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Requisitions', 'url' => route('admin.requisitions.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Purchase Requisition</h1>
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-3">{{ $e }}</div>@endforeach

    <form method="POST" action="{{ route('admin.requisitions.store') }}" class="panel p-6 max-w-2xl space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Title *</label><input name="title" value="{{ old('title') }}" class="form-input mt-1" required></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Category *</label>
                <select name="category" class="form-select mt-1">@foreach($categories as $v=>$l)<option value="{{ $v }}" @selected(old('category')===$v)>{{ $l }}</option>@endforeach</select>
            </div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Requested Amount *</label><input type="number" step="0.01" name="requested_amount" value="{{ old('requested_amount') }}" class="form-input mt-1" required></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Estimated Amount</label><input type="number" step="0.01" name="estimated_amount" value="{{ old('estimated_amount') }}" class="form-input mt-1"></div>
        </div>
        <div><label class="text-xs font-semibold text-gray-500 uppercase">Purpose</label><textarea name="purpose" rows="3" class="form-textarea mt-1">{{ old('purpose') }}</textarea></div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Submit Requisition</button>
            <a href="{{ route('admin.requisitions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
