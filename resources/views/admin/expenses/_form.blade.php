@php $expense ??= null; @endphp

@if ($errors->any())
    <div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">
        <ul class="list-disc ml-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@php
    // Pre-build the categoryId → subcategories map so the dropdown renders
    // the correct list immediately on edit (no flicker of an AJAX hit).
    $catSubMap = $categories->mapWithKeys(fn ($c) => [$c->id => $c->subcategories->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()]);
@endphp

<div
    x-data="{
        type: @js(old('type', $expense?->type ?? 'one_off')),
        categoryId: @js((int) old('expense_category_id', $expense?->expense_category_id)),
        subcategoryId: @js((int) old('expense_subcategory_id', $expense?->expense_subcategory_id)),
        catSubMap: @js($catSubMap),

        get subcategories() {
            return this.catSubMap[this.categoryId] || [];
        },
        get hasSubcategories() {
            return this.subcategories.length > 0;
        },
        onCategoryChange() {
            // Reset subcategory when category changes (unless we're keeping a valid one)
            const validIds = this.subcategories.map(s => s.id);
            if (!validIds.includes(this.subcategoryId)) this.subcategoryId = null;
        },
    }"
>
    {{-- Type toggle --}}
    <div class="mb-4">
        <label class="form-label">Payment Type <span class="text-danger">*</span></label>
        <div class="flex gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="type" value="one_off" x-model="type" class="form-radio">
                <span>One-off</span>
                <span class="text-xs text-gray-500">— ad-hoc / single occurrence</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="type" value="recurring" x-model="type" class="form-radio">
                <span>Monthly Recurring</span>
                <span class="text-xs text-gray-500">— fixed day every month, sends reminders</span>
            </label>
        </div>
    </div>

    @php
        // Server-side resolved values, used as authoritative initial selection.
        // Alpine's x-model alone wasn't reliably preselecting the option on edit
        // because options render after the select element initialises.
        $selectedCategoryId = (int) old('expense_category_id', $expense?->expense_category_id);
        $selectedSubcategoryId = (int) old('expense_subcategory_id', $expense?->expense_subcategory_id);
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="expense_category_id" class="form-select" x-model.number="categoryId" @change="onCategoryChange()" required>
                <option value="">— Select —</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected($selectedCategoryId === (int) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Subcategory only shows when chosen category has subcategories.
             :disabled when hidden so the input isn't submitted at all,
             otherwise the empty/null value tries to validate as integer. --}}
        <div x-show="hasSubcategories" x-cloak>
            <label class="form-label">Subcategory <span class="text-xs text-gray-500">(optional)</span></label>
            <select name="expense_subcategory_id" class="form-select" x-model="subcategoryId" :disabled="!hasSubcategories">
                <option value="" :selected="!subcategoryId">— None —</option>
                <template x-for="sub in subcategories" :key="sub.id">
                    <option :value="sub.id" x-text="sub.name" :selected="sub.id === subcategoryId"></option>
                </template>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-input" value="{{ old('title', $expense?->title) }}" required>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" rows="2">{{ old('description', $expense?->description) }}</textarea>
        </div>

        <div>
            <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="amount" class="form-input" value="{{ old('amount', $expense?->amount) }}" required>
        </div>
        <div>
            <label class="form-label">Bill Date <span class="text-danger">*</span></label>
            <input type="date" name="expense_date" class="form-input" value="{{ old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString()) }}" required>
        </div>

        {{-- One-off due_date --}}
        <div x-show="type === 'one_off'" x-cloak>
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-input" value="{{ old('due_date', $expense?->due_date?->toDateString()) }}">
            <p class="text-xs text-gray-500 mt-1">Optional. If set, reminders fire 3 days before.</p>
        </div>

        {{-- Recurring day-of-month --}}
        <div x-show="type === 'recurring'" x-cloak>
            <label class="form-label">Due Day of Month <span class="text-danger">*</span></label>
            <input type="number" min="1" max="28" name="due_day_of_month" class="form-input" value="{{ old('due_day_of_month', $expense?->due_day_of_month ?? 1) }}">
            <p class="text-xs text-gray-500 mt-1">1–28. Reminders fire 3 days, 1 day, on the day, then daily until paid.</p>
        </div>

        <div>
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select">
                <option value="">—</option>
                @foreach(['bank' => 'Bank Transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'upi' => 'UPI', 'card' => 'Card'] as $k => $v)
                    <option value="{{ $k }}" @selected(old('payment_method', $expense?->payment_method) === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Payment Reference</label>
            <input type="text" name="payment_reference" class="form-input" value="{{ old('payment_reference', $expense?->payment_reference) }}" placeholder="UTR / cheque no. / etc.">
        </div>

        <div class="md:col-span-2">
            <label class="form-label">Receipt Attachment</label>
            <input type="file" name="attachment" class="form-input" accept="image/*,application/pdf">
            @if($expense?->attachment)
                <a href="{{ asset('storage/'.$expense->attachment) }}" target="_blank" class="text-primary text-xs mt-1 inline-block">View current attachment</a>
            @endif
        </div>
    </div>
</div>
