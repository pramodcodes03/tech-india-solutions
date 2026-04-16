<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Edit User</h5>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">
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

            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required />
                    </div>
                    <div>
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required />
                    </div>
                    <div>
                        <label for="mobile">Mobile</label>
                        <input id="mobile" name="mobile" type="text" class="form-input" value="{{ old('mobile', $user->mobile) }}" />
                    </div>
                    <div>
                        <label for="city_id">City</label>
                        <x-admin.searchable-select name="city_id" :options="$cities" :selected="$user->city_id" placeholder="-- Select City --" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="address">Address</label>
                        <input id="address" name="address" type="text" class="form-input" value="{{ old('address', $user->address) }}" />
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div></div>
                    <div>
                        <label for="password">Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                        <input id="password" name="password" type="password" class="form-input" />
                    </div>
                    <div>
                        <label for="password_confirmation">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
