<x-layout.employee title="Raise Ticket">
    <h1 class="text-2xl font-extrabold mb-4">Raise a Ticket</h1>
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-3">{{ $e }}</div>@endforeach

    <form method="POST" action="{{ route('employee.tickets.store') }}" class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow max-w-2xl space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Department *</label>
                <select name="department" id="deptSelect" class="form-select mt-1" onchange="filterCats()">
                    @foreach(\App\Models\InternalTicket::DEPARTMENTS as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Category</label>
                <select name="category_id" id="catSelect" class="form-select mt-1">
                    <option value="">— General —</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}" data-dept="{{ $c->department }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Priority *</label>
                <select name="priority" class="form-select mt-1">@foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected($p==='medium')>{{ ucfirst($p) }}</option>@endforeach</select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Subject *</label>
                <input name="subject" value="{{ old('subject') }}" class="form-input mt-1" required>
            </div>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Description *</label>
            <textarea name="description" rows="5" class="form-textarea mt-1" required>{{ old('description') }}</textarea>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Submit</button>
            <a href="{{ route('employee.tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

    <script>
        function filterCats() {
            const dept = document.getElementById('deptSelect').value;
            document.querySelectorAll('#catSelect option[data-dept]').forEach(o => {
                o.style.display = o.getAttribute('data-dept') === dept ? '' : 'none';
            });
            document.getElementById('catSelect').value = '';
        }
        document.addEventListener('DOMContentLoaded', filterCats);
    </script>
</x-layout.employee>
