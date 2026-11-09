@extends('layouts.panel')

@section('title', 'Categories')
@section('heading', 'Categories')

@section('content')
@php
    // True when the last submit was a failed EDIT, so the add form
    // doesn't get pre-filled with the edit form's old input.
    $editFailed = $errors->editCategory->any();

    // Popup message (success or error), if any
    $popupSuccess = session('categories_success');
    $popupError   = session('categories_error');
@endphp


<div class="stat-panel">
    <div class="row row-cols-1 row-cols-sm-2 g-3">

        <div class="col">
            <div class="stat-card stat-card--teal">
                <i class="bi bi-tags stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Total Categories</p>
                    <p class="stat-value">{{ $totalCategories }}</p>
                </div>
            </div>
        </div>

        <div class="col">
            <a href="#"
                class="stat-card stat-card--cyan"
                data-bs-toggle="modal"
                data-bs-target="#addCategory">
                <i class="bi bi-plus-circle stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Action</p>
                    <p class="stat-value">Add New Category</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>

    </div>
</div>


<section class="section-block">

    <div class="d-flex flex-column-reverse flex-sm-row justify-content-between align-items-sm-center gap-3">

        <div class="search-toolbar d-flex align-items-center gap-2">
            <form class="search-group input-group"
                action="{{ route('categories') }}"
                method="GET"
                role="search">

                <input type="search"
                    class="form-control"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Category name"
                    aria-label="Search categories">

                <button class="btn btn-info text-white" type="submit">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </form>
        </div>

        <div class="text-sm-end">
            <h2 class="section-title">All Categories</h2>
            <p class="section-subtitle">Click to view books in a category</p>
        </div>

    </div>

    <div class="table-responsive">
        <table class="table data-table">

            <thead>
                <tr>
                    <th scope="col">Category ID</th>
                    <th scope="col">Category Name</th>
                    <th scope="col">Books</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($categories as $category)
                <tr>
                    <td>CAT-{{ $category->category_id }}</td>
                    <td>
                        <a href="{{ route('categories.show', $category->category_id) }}"
                            class="text-decoration-none fw-semibold">
                            {{ $category->category_name }}
                        </a>
                    </td>
                    <td>{{ $category->books_count }}</td>

                    <td class="text-nowrap">

                        <a href="{{ route('categories.show', $category->category_id) }}"
                            class="btn btn-sm btn-outline-primary"
                            aria-label="View category books">
                            <i class="bi bi-eye"></i>
                        </a>

                        <button type="button"
                            class="btn btn-sm btn-outline-success edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editCategory"
                            data-category-id="{{ $category->category_id }}"
                            data-url="{{ route('categories.update', $category->category_id) }}"
                            data-name="{{ $category->category_name }}"
                            aria-label="Edit category">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteCategory"
                            data-name="{{ $category->category_name }}"
                            data-books="{{ $category->books_count }}"
                            data-url="{{ route('categories.destroy', $category->category_id) }}"
                            aria-label="Delete category">
                            <i class="bi bi-trash"></i>
                        </button>

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="table-empty">
                        <i class="bi bi-inbox"></i>
                        @if (request('search'))
                        No categories match your search "{{ request('search') }}".
                        @else
                        No categories to display yet.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    <div class="mt-3">
        {{ $categories->links() }}
    </div>

</section>


<!-- ====================================== -->
<!-- RESULT POPUP (success / error)         -->
<!-- ====================================== -->
@if ($popupSuccess || $popupError)
    <div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center p-3">
                <div class="modal-body">
                    @if ($popupSuccess)
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold mt-2">Success</h5>
                        <p class="mb-0">{{ $popupSuccess }}</p>
                    @else
                        <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold mt-2">Unable to continue</h5>
                        <p class="mb-0">{{ $popupError }}</p>
                    @endif
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button"
                            class="btn {{ $popupSuccess ? 'btn-info text-white' : 'btn-danger' }} px-4"
                            data-bs-dismiss="modal">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif


<!-- ====================================== -->
<!-- ADD CATEGORY MODAL                     -->
<!-- ====================================== -->
<div class="modal fade"
    id="addCategory"
    tabindex="-1"
    aria-hidden="true"
    data-show-on-load="{{ $errors->any() && ! $editFailed ? '1' : '0' }}">

    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('categories.store') }}" class="modal-content p-3">
            @csrf

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold">Add New Category</h5>
                    <small class="text-muted">Enter the category name below.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                @if ($errors->any() && ! $editFailed)
                <div class="alert alert-danger">
                    <strong>
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Please check the following:
                    </strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <label for="category_name" class="form-label">
                    Category Name <span class="text-danger">*</span>
                </label>

                <input type="text"
                    id="category_name"
                    name="category_name"
                    value="{{ $editFailed ? '' : old('category_name') }}"
                    class="form-control @error('category_name') is-invalid @enderror"
                    placeholder="Enter category name"
                    maxlength="255"
                    required>

                @error('category_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror

            </div>

            <div class="modal-footer border-0">
                <button type="reset" class="btn btn-outline-secondary">Clear</button>
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-plus-circle me-1"></i> Add Category
                </button>
            </div>

        </form>
    </div>
</div>


<!-- ====================================== -->
<!-- EDIT CATEGORY MODAL                    -->
<!-- ====================================== -->
<div class="modal fade"
    id="editCategory"
    tabindex="-1"
    aria-labelledby="editCategoryLabel"
    aria-hidden="true"
    data-show-on-load="{{ $editFailed ? '1' : '0' }}">

    <div class="modal-dialog modal-dialog-centered">
        <form method="POST"
            id="editCategoryForm"
            action="{{ $editFailed && old('edit_category_id') ? route('categories.update', old('edit_category_id')) : '' }}"
            class="modal-content p-3">

            @csrf
            @method('PUT')

            <input type="hidden"
                name="edit_category_id"
                id="edit_category_id"
                value="{{ $editFailed ? old('edit_category_id') : '' }}">

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold" id="editCategoryLabel">Edit Category</h5>
                    <small class="text-muted">Update the category name below.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                @if ($editFailed)
                <div class="alert alert-danger" id="editErrors">
                    <strong>
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Please check the following:
                    </strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->editCategory->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <label for="edit_category_name" class="form-label">
                    Category Name <span class="text-danger">*</span>
                </label>

                <input type="text"
                    id="edit_category_name"
                    name="category_name"
                    value="{{ $editFailed ? old('category_name') : '' }}"
                    class="form-control @if($errors->editCategory->has('category_name')) is-invalid @endif"
                    maxlength="255"
                    required>

                @if ($errors->editCategory->has('category_name'))
                <div class="invalid-feedback">
                    {{ $errors->editCategory->first('category_name') }}
                </div>
                @endif

            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-check-circle me-1"></i> Save Changes
                </button>
            </div>

        </form>
    </div>
</div>


<!-- ====================================== -->
<!-- DELETE CATEGORY MODAL                  -->
<!-- ====================================== -->
<div class="modal fade" id="deleteCategory" tabindex="-1" aria-labelledby="deleteCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold" id="deleteCategoryLabel">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-2">Are you sure you want to delete this category?</p>
                <p class="fw-semibold mb-3" id="deleteCategoryName"></p>

                <div class="alert alert-danger mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This action cannot be undone. Categories that still have books cannot be deleted.
                </div>

                <div class="alert alert-warning mt-3 mb-0 d-none" id="deleteCategoryWarning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This category has <strong id="deleteCategoryBooks"></strong> book(s) assigned to it.
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteCategoryForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete Category
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('category.js') }}"></script>
@endpush