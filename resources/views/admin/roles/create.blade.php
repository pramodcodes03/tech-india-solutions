<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Add Role</h5>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <div class="panel">
            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Role Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name') }}" required />
                    </div>
                </div>

                <div class="mt-6" x-data="permissionGrid">
                    <h6 class="text-base font-semibold mb-4 dark:text-white-light">Permissions</h6>
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2">Module</th>
                                    <th class="px-4 py-2 !text-center">Select All</th>
                                    @foreach($actions as $action)
                                        <th class="px-4 py-2 !text-center">{{ ucfirst($action) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $module)
                                    <tr>
                                        <td class="px-4 py-2 font-semibold">{{ ucfirst(str_replace('_', ' ', $module)) }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="checkbox"
                                                class="form-checkbox"
                                                @change="toggleModule('{{ $module }}')"
                                                :checked="isModuleChecked('{{ $module }}')" />
                                        </td>
                                        @foreach($actions as $action)
                                            @php $permissionName = $module . '.' . $action; @endphp
                                            @if(in_array($permissionName, $allPermissions))
                                                <td class="px-4 py-2 text-center">
                                                    <input type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permissionName }}"
                                                        class="form-checkbox permission-{{ $module }}"
                                                        {{ in_array($permissionName, old('permissions', [])) ? 'checked' : '' }}
                                                        @change="updateModuleState('{{ $module }}')" />
                                                </td>
                                            @else
                                                <td class="px-4 py-2 text-center">
                                                    <span class="text-gray-300">-</span>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('permissionGrid', () => ({
                toggleModule(module) {
                    const checkboxes = this.$el.querySelectorAll(`.permission-${module}`);
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    checkboxes.forEach(cb => cb.checked = !allChecked);
                },
                isModuleChecked(module) {
                    const checkboxes = this.$el.querySelectorAll(`.permission-${module}`);
                    return checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
                },
                updateModuleState(module) {
                    // Reactivity trigger for Alpine to re-evaluate isModuleChecked
                    this.$nextTick(() => {});
                }
            }));
        });
    </script>
</x-layout.admin>
