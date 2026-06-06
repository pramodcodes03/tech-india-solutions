<x-layout.admin title="Edit Candidate">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => 'Edit']]" />
    <h1 class="text-2xl font-extrabold mb-5">Edit Candidate — {{ $candidate->full_name }}</h1>
    <form method="POST" action="{{ route('admin.hr.recruitment.update', $candidate) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.hr.recruitment._form', ['candidate' => $candidate])
        <div class="flex gap-3 mt-5">
            <button class="btn btn-primary">Update Candidate</button>
            <a href="{{ route('admin.hr.recruitment.show', $candidate) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
