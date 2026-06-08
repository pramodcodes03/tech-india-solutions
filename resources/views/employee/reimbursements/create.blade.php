<x-layout.employee title="New Reimbursement Claim">
    <h1 class="text-2xl font-extrabold mb-4">New Reimbursement Claim</h1>
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-3">{{ $e }}</div>@endforeach

    <form method="POST" action="{{ route('employee.reimbursements.store') }}" enctype="multipart/form-data" class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow max-w-2xl space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Title *</label><input name="title" value="{{ old('title') }}" class="form-input mt-1" required></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Category</label>
                <select name="expense_category_id" class="form-select mt-1"><option value="">— Select —</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('expense_category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
            </div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Amount *</label><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="form-input mt-1" required></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Claim Date *</label><input type="date" name="claim_date" value="{{ old('claim_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" class="form-input mt-1" required></div>
        </div>
        <div><label class="text-xs font-semibold text-gray-500 uppercase">Purpose</label><textarea name="purpose" rows="3" class="form-textarea mt-1">{{ old('purpose') }}</textarea></div>
        <div><label class="text-xs font-semibold text-gray-500 uppercase">Bill / Receipt *</label><input type="file" name="bill" accept=".pdf,.jpg,.jpeg,.png" class="form-input mt-1" required></div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Submit Claim</button>
            <a href="{{ route('employee.reimbursements.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.employee>
