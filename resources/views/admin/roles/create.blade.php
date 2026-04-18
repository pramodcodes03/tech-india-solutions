<x-layout.admin title="Create Role">
<style>
:root { --v: #7C3AEC; --v2: #6D28D9; --v3: #A855F7; --vl: rgba(124,58,236,0.08); }

.perm-cb { position: absolute; opacity: 0; width: 0; height: 0; }
.perm-cb + .cb-box {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 6px;
    border: 2px solid #d1d5db; background: #fff;
    cursor: pointer; transition: all 0.18s ease; flex-shrink: 0;
}
.dark .perm-cb + .cb-box { background: #1e2a3b; border-color: #374151; }
.perm-cb:checked + .cb-box { background: var(--v); border-color: var(--v); box-shadow: 0 2px 8px rgba(124,58,236,0.35); }
.perm-cb:checked + .cb-box svg { display: block; }
.perm-cb + .cb-box svg { display: none; }
.perm-cb:hover + .cb-box { border-color: var(--v); }

.mod-cb { position: absolute; opacity: 0; width: 0; height: 0; }
.mod-cb + .mod-box {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 7px;
    border: 2px solid #d1d5db; background: #fff;
    cursor: pointer; transition: all 0.18s ease;
}
.dark .mod-cb + .mod-box { background: #1e2a3b; border-color: #374151; }
.mod-cb:checked + .mod-box { background: var(--v); border-color: var(--v); box-shadow: 0 2px 8px rgba(124,58,236,0.35); }
.mod-cb:checked + .mod-box svg { display: block; }
.mod-cb + .mod-box svg { display: none; }

.perm-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.perm-table thead tr th {
    position: sticky; top: 0; z-index: 10;
    background: var(--v); color: #fff;
    padding: 13px 16px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px; border-bottom: none;
}
.perm-table thead tr th:first-child { border-radius: 10px 0 0 0; }
.perm-table thead tr th:last-child  { border-radius: 0 10px 0 0; }
.perm-table thead tr th.text-center { text-align: center; }
.perm-table tbody tr { transition: background 0.15s; }
.perm-table tbody tr:nth-child(even) { background: rgba(124,58,236,0.03); }
.perm-table tbody tr:hover { background: rgba(124,58,236,0.07) !important; }
.dark .perm-table tbody tr:nth-child(even) { background: rgba(124,58,236,0.05); }
.dark .perm-table tbody tr:hover { background: rgba(124,58,236,0.1) !important; }
.perm-table tbody td { padding: 11px 16px; border-bottom: 1px solid rgba(0,0,0,0.05); vertical-align: middle; }
.dark .perm-table tbody td { border-bottom-color: rgba(255,255,255,0.04); }
.perm-table tbody tr:last-child td { border-bottom: none; }
.perm-table td.text-center { text-align: center; }

.mod-name { font-weight: 700; font-size: 13px; color: #1f2937; display: flex; align-items: center; gap: 10px; }
.dark .mod-name { color: #e2e8f0; }
.mod-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--vl); }
.mod-icon svg { width: 15px; height: 15px; color: var(--v); }

.th-selectall { background: rgba(255,255,255,0.15) !important; border-left: 1px solid rgba(255,255,255,0.15); border-right: 1px solid rgba(255,255,255,0.15); }
.td-selectall { background: rgba(124,58,236,0.04); border-left: 1px solid rgba(124,58,236,0.08); border-right: 1px solid rgba(124,58,236,0.08); }
.perm-dash { color: #d1d5db; font-size: 16px; display: block; text-align: center; }
.prog-bar-wrap { height: 3px; background: #e5e7eb; border-radius: 99px; margin-top: 3px; width: 60px; }
.prog-bar { height: 3px; border-radius: 99px; background: linear-gradient(90deg, var(--v), var(--v3)); transition: width 0.3s; }

.role-input { border: 2px solid #e5e7eb; border-radius: 10px; padding: 11px 16px; font-size: 15px; font-weight: 600; transition: border-color 0.2s, box-shadow 0.2s; outline: none; width: 100%; background: #fff; color: #1f2937; }
.role-input:focus { border-color: var(--v); box-shadow: 0 0 0 3px rgba(124,58,236,0.12); }
.dark .role-input { background: #121e32; color: #e2e8f0; border-color: #17263c; }
.btn-v { background: var(--v); color: #fff; border-color: var(--v); }
.btn-v:hover { background: var(--v2); color: #fff; box-shadow: 0 4px 14px rgba(124,58,236,0.4); }

.filter-input { border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 8px 14px 8px 36px; font-size: 13px; outline: none; transition: border-color 0.2s; background: #fff; color: #374151; }
.dark .filter-input { background: #121e32; color: #e2e8f0; border-color: #17263c; }
.filter-input:focus { border-color: var(--v); }
.filter-wrap { position: relative; }
.filter-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #9ca3af; }

.global-all { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; cursor: pointer; border: 1.5px solid var(--v); color: var(--v); font-size: 13px; font-weight: 600; transition: all 0.2s; background: transparent; }
.global-all:hover, .global-all.active { background: var(--v); color: #fff; }
</style>

<div x-data="permGrid()" x-init="init()">
    <x-admin.breadcrumb :items="[['label'=>'Roles','url'=>route('admin.roles.index')],['label'=>'Create Role']]" />

    <div class="flex items-center justify-between mb-6">
        <div>
            <h5 class="text-xl font-bold dark:text-white">Create New Role</h5>
            <p class="text-sm text-gray-500 mt-0.5">Define a name and set module-level permissions</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
            @foreach ($errors->all() as $error)<p class="text-sm text-danger">{{ $error }}</p>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.roles.store') }}" method="POST">
        @csrf

        <div class="panel mb-5">
            <div class="flex flex-wrap items-end gap-6">
                <div class="flex-1 min-w-[220px]">
                    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2 block">Role Name <span class="text-danger">*</span></label>
                    <input id="name" name="name" type="text" class="role-input"
                        value="{{ old('name') }}" required placeholder="e.g. Sales Manager, Accountant…" />
                </div>
                <div class="flex gap-4">
                    <div class="text-center px-5 py-3 rounded-xl" style="background:var(--vl)">
                        <div class="text-2xl font-black" style="color:var(--v)" x-text="checkedCount">0</div>
                        <div class="text-xs text-gray-500 font-medium">Selected</div>
                    </div>
                    <div class="text-center px-5 py-3 rounded-xl" style="background:rgba(59,130,246,0.08)">
                        <div class="text-2xl font-black text-info">{{ count($modules) }}</div>
                        <div class="text-xs text-gray-500 font-medium">Modules</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--vl)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:var(--v)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <h6 class="font-bold text-gray-800 dark:text-white">Module Permissions</h6>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="filter-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" x-model="search" placeholder="Search module…" class="filter-input" />
                    </div>
                    <button type="button" class="global-all" :class="{ active: allChecked }" @click="toggleAll(!allChecked)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="allChecked ? 'Deselect All' : 'Select All'">Select All</span>
                    </button>
                </div>
            </div>

            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-[#1b2e4b]">
                <div class="overflow-x-auto">
                    <table class="perm-table">
                        <thead>
                            <tr>
                                <th style="width:220px; text-align:left;">Module</th>
                                <th class="th-selectall text-center" style="width:90px;">Select All</th>
                                @foreach($actions as $action)
                                    <th class="text-center">{{ ucfirst($action) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $module)
                                @php
                                    $modulePerms = array_filter($allPermissions, fn($p) => str_starts_with($p, $module.'.'));
                                    $hasAny = count($modulePerms) > 0;
                                    $moduleActions = array_map(fn($p) => explode('.', $p)[1], $modulePerms);
                                    $moduleSlug = str_replace([' ', '_'], '-', $module);
                                    $icons = [
                                        'categories'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>',
                                        'customers'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                        'dashboard'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
                                        'goods_receipts' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                                        'inventory'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>',
                                        'invoices'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                                        'leads'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                                        'payments'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
                                        'products'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                                        'purchase_orders'=> '<path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
                                        'quotations'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>',
                                        'reports'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                                        'roles'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                                        'sales_orders'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
                                        'service_tickets'=> '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>',
                                        'settings'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                        'vendors'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                                        'warehouses'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>',
                                    ];
                                    $iconPath = $icons[$module] ?? '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>';
                                @endphp
                                <tr x-show="!search || '{{ strtolower(str_replace(['_','-'], ' ', $module)) }}'.includes(search.toLowerCase())">
                                    <td>
                                        <div class="mod-name">
                                            <div class="mod-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $iconPath !!}</svg>
                                            </div>
                                            <div>
                                                <div>{{ ucwords(str_replace(['_','-'], ' ', $module)) }}</div>
                                                @if($hasAny)
                                                <div class="prog-bar-wrap">
                                                    <div class="prog-bar" id="prog-{{ $moduleSlug }}" style="width:0%"></div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="td-selectall text-center">
                                        @if($hasAny)
                                        <label style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer;">
                                            <input type="checkbox" class="mod-cb permission-mod-{{ $moduleSlug }}"
                                                id="mod-{{ $moduleSlug }}"
                                                :checked="isModuleChecked('{{ $moduleSlug }}')"
                                                @change="toggleModule('{{ $moduleSlug }}', $event.target.checked)" />
                                            <span class="mod-box">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                        </label>
                                        @else
                                        <span class="perm-dash">—</span>
                                        @endif
                                    </td>
                                    @foreach($actions as $action)
                                        @php $permName = $module.'.'.$action; @endphp
                                        @if(in_array($permName, $allPermissions))
                                            <td class="text-center">
                                                <label style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer;">
                                                    <input type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permName }}"
                                                        class="perm-cb permission-{{ $moduleSlug }}"
                                                        id="p-{{ str_replace(['.','_','-'], '-', $permName) }}"
                                                        {{ in_array($permName, old('permissions', [])) ? 'checked' : '' }}
                                                        @change="onPermChange('{{ $moduleSlug }}')" />
                                                    <span class="cb-box">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    </span>
                                                </label>
                                            </td>
                                        @else
                                            <td class="text-center"><span class="perm-dash">—</span></td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-5">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-v gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Save Role
            </button>
        </div>
    </form>
</div>

<script>
function permGrid() {
    return {
        checkedCount: 0,
        allChecked: false,
        search: '',
        init() { this.$nextTick(() => this.updateAll()); },
        updateAll() {
            const all = document.querySelectorAll('.perm-cb');
            const checked = document.querySelectorAll('.perm-cb:checked');
            this.checkedCount = checked.length;
            this.allChecked = all.length > 0 && checked.length === all.length;
        },
        toggleAll(val) {
            this.allChecked = val;
            document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = val);
            document.querySelectorAll('[class*="permission-mod-"]').forEach(cb => cb.checked = val);
            document.querySelectorAll('[id^="prog-"]').forEach(bar => bar.style.width = val ? '100%' : '0%');
            this.updateAll();
        },
        toggleModule(module, checked) {
            document.querySelectorAll(`.permission-${module}`).forEach(cb => cb.checked = checked);
            this.updateProgress(module);
            this.updateAll();
        },
        isModuleChecked(module) {
            const cbs = document.querySelectorAll(`.permission-${module}`);
            return cbs.length > 0 && Array.from(cbs).every(cb => cb.checked);
        },
        onPermChange(module) {
            const cbs = document.querySelectorAll(`.permission-${module}`);
            const modCb = document.querySelector(`.permission-mod-${module}`);
            if (modCb) modCb.checked = Array.from(cbs).every(cb => cb.checked);
            this.updateProgress(module);
            this.updateAll();
        },
        updateProgress(module) {
            const cbs = document.querySelectorAll(`.permission-${module}`);
            const bar = document.getElementById(`prog-${module}`);
            if (!bar || cbs.length === 0) return;
            bar.style.width = Math.round(Array.from(cbs).filter(c => c.checked).length / cbs.length * 100) + '%';
        }
    }
}
</script>
</x-layout.admin>
