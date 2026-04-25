<x-layout.admin title="Edit User">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Users','url'=>route('admin.admin-users.index')],['label'=>'Edit User']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Edit Admin User</h5>
            <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-primary">
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

            <form action="{{ route('admin.admin-users.update', $adminUser->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $adminUser->name) }}" required />
                    </div>
                    <div>
                        <label for="business_name">Business Name</label>
                        <input id="business_name" name="business_name" type="text" class="form-input" value="{{ old('business_name', $adminUser->business_name) }}" />
                    </div>
                    <div>
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $adminUser->email) }}" required />
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone', $adminUser->phone) }}" />
                    </div>
                    <div>
                        <label for="role_id">Role <span class="text-danger">*</span></label>
                        <x-admin.searchable-select name="role_id" :options="$roles" :selected="$adminUser->role_id" placeholder="-- Select Role --" required />
                    </div>
                    <div>
                        <label for="password">Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                        <input id="password" name="password" type="password" class="form-input" />
                    </div>
                    <div>
                        <label for="password_confirmation">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" />
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" {{ old('status', $adminUser->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $adminUser->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Admin User</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
