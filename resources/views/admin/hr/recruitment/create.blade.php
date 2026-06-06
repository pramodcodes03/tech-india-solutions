<x-layout.admin title="Add Candidate">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => 'Add Candidate']]" />
    <h1 class="text-2xl font-extrabold mb-5">Add Candidate</h1>
    <form method="POST" action="{{ route('admin.hr.recruitment.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.hr.recruitment._form')
        <div class="flex gap-3 mt-5">
            <button class="btn btn-primary">Save Candidate</button>
            <a href="{{ route('admin.hr.recruitment.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
