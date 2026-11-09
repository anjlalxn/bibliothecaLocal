@extends('layouts.panel')

@section('title', 'Books')
@section('heading', 'Books')

@push('styles')
<link href="{{ asset('books.css') }}" rel="stylesheet">
@endpush

@section('content')
@php
    // True when the last submit was a failed EDIT, so the add form
    // doesn't get pre-filled with the edit form's old input.
    $editFailed = $errors->editBook->any();

    // Filter keys that need to survive a search submit
    $filterKeys = ['category_id', 'status', 'year_from', 'year_to', 'sort'];

    // Popup message (success or error), if any
    $popupSuccess = session('books_success');
    $popupError   = session('books_error');
@endphp


<div class="stat-panel">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">

        <div class="col">
            <div class="stat-card bg-dark text-white">
                <i class="bi bi-journal-bookmark stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Total Books</p>
                    <p class="stat-value">{{ number_format($totalTitles) }}</p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="stat-card stat-card--teal">
                <i class="bi bi-book stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Total Copies</p>
                    <p class="stat-value">{{ number_format($totalBooks) }}</p>
                </div>
            </div>
        </div>

        <div class="col">
            <a href="#" class="stat-card stat-card--cyan" data-bs-toggle="modal" data-bs-target="#addBook">
                <i class="bi bi-plus-circle stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Action</p>
                    <p class="stat-value">Add New Book</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>

    </div>
</div>


<section class="section-block">

    <div class="d-flex flex-column-reverse flex-sm-row justify-content-sm-between align-items-sm-center gap-3">

        <div class="search-toolbar d-flex align-items-center gap-2">

            <form class="search-group input-group" action="{{ route('books') }}" method="GET" role="search">

                {{-- Keep active filters when searching --}}
                @foreach ($filterKeys as $key)
                    @if (request()->filled($key))
                        <input type="hidden" name="{{ $key }}" value="{{ request($key) }}">
                    @endif
                @endforeach

                <input type="search" class="form-control" name="search" value="{{ request('search') }}"
                       placeholder="Author/Category/Book" aria-label="Search books">

                <button class="btn btn-info text-white" type="submit">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </form>

            <button class="btn btn-filter" type="button" data-bs-toggle="modal"
                    data-bs-target="#filterBooks" aria-label="Filter">
                <i class="bi bi-filter"></i>
                @if ($activeFilterCount > 0)
                    <span class="filter-badge">{{ $activeFilterCount }}</span>
                @endif
            </button>

        </div>

        <div class="text-sm-end">
            <h2 class="section-title">All Books</h2>
        </div>

    </div>

    
    @if ($activeFilterCount > 0)
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small text-muted">
            <span>
                <i class="bi bi-funnel me-1"></i>
                {{ $books->total() }}
                {{ \Illuminate\Support\Str::plural('result', $books->total()) }}
                with {{ $activeFilterCount }}
                {{ \Illuminate\Support\Str::plural('filter', $activeFilterCount) }}
                applied
            </span>
            <a href="{{ route('books', array_filter(['search' => request('search')])) }}"
               class="link-danger text-decoration-none">
                <i class="bi bi-x-circle me-1"></i>Clear filters
            </a>
        </div>
    @endif

    
    <div class="table-responsive">
        <table class="table data-table">

            <thead>
                <tr>
                    <th scope="col">Book ID</th>
                    <th scope="col">Category</th>
                    <th scope="col">Title</th>
                    <th scope="col">Author</th>
                    <th scope="col">Publisher</th>
                    <th scope="col">Year</th>
                    <th scope="col">Qty</th>
                    <th scope="col">Avail</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($books as $book)
                    @php
                        $isAvailable = $book->available_quantity > 0
                            && strtolower((string) $book->status) === 'available';
                        $year = $book->publication_year ?: 'Unknown Year';
                    @endphp

                    <tr>
                        <td>BOOK-{{ $book->book_id }}</td>
                        <td>{{ $book->category->category_name ?? '—' }}</td>
                        <td>{{ $book->title }}</td>
                        <td>{{ $book->author }}</td>
                        <td>{{ $book->publisher ?: '—' }}</td>
                        <td>{{ $year }}</td>
                        <td>{{ $book->quantity }}</td>
                        <td>{{ $book->available_quantity }}</td>
                        <td>
                            @if ($isAvailable)
                                <span class="badge text-bg-success">Available</span>
                            @else
                                <span class="badge text-bg-danger">Unavailable</span>
                            @endif
                        </td>

                        <td class="text-nowrap">

                            
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary view-btn"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#viewBook"
                                    data-id="BOOK-{{ $book->book_id }}"
                                    data-title="{{ $book->title }}"
                                    data-author="{{ $book->author }}"
                                    data-publisher="{{ $book->publisher }}"
                                    data-year="{{ $year }}"
                                    data-qty="{{ $book->quantity }}"
                                    data-category="{{ $book->category->category_name ?? '—' }}"
                                    data-status="{{ $isAvailable ? 'Available' : 'Unavailable' }} ({{ $book->available_quantity }} copies)"
                                    aria-label="View book">
                                <i class="bi bi-eye"></i>
                            </button>

                            
                            <button type="button"
                                    class="btn btn-sm btn-outline-success edit-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editBook"
                                    data-book-id="{{ $book->book_id }}"
                                    data-url="{{ route('books.update', $book->book_id) }}"
                                    data-title="{{ $book->title }}"
                                    data-author="{{ $book->author }}"
                                    data-category-id="{{ $book->category_id }}"
                                    data-publisher="{{ $book->publisher }}"
                                    data-year="{{ $book->publication_year }}"
                                    data-qty="{{ $book->quantity }}"
                                    data-status="{{ strtolower((string) $book->status) }}"
                                    data-borrowed="{{ max(0, $book->quantity - $book->available_quantity) }}"
                                    aria-label="Edit book">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger delete-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteBook"
                                    data-title="{{ $book->title }}"
                                    data-url="{{ route('books.destroy', $book->book_id) }}"
                                    aria-label="Delete book">
                                <i class="bi bi-trash"></i>
                            </button>

                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="10" class="table-empty">
                            <i class="bi bi-inbox"></i>
                            @if (request('search') || $activeFilterCount > 0)
                                No books match your
                                @if (request('search'))
                                    search "{{ request('search') }}"
                                    @if ($activeFilterCount > 0) and @endif
                                @endif
                                @if ($activeFilterCount > 0)
                                    filters
                                @endif.
                            @else
                                No books to display yet.
                            @endif
                        </td>
                    </tr>

                @endforelse
            </tbody>

        </table>
    </div>

    
    <div class="mt-3">
        {{ $books->links() }}
    </div>

</section>




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




<div class="modal fade" id="filterBooks" tabindex="-1" aria-labelledby="filterBooksLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

        <form method="GET" action="{{ route('books') }}" class="modal-content p-3">

            {{-- Keep the current search term when filtering --}}
            @if (request()->filled('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold" id="filterBooksLabel">Filter Books</h5>
                    <small class="text-muted">Narrow down the list. Filters work together with search.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">

                    
                    <div class="col-12">
                        <label for="filter_category_id" class="form-label">Category</label>
                        <select name="category_id" id="filter_category_id" class="form-select">
                            <option value="">All categories</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->category_id }}"
                                    @selected($filters['category_id'] === (int) $c->category_id)>
                                    {{ $c->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    
                    <div class="col-12">
                        <label for="filter_status" class="form-label">Status</label>
                        <select name="status" id="filter_status" class="form-select">
                            <option value="">Any status</option>
                            <option value="available" @selected($filters['status'] === 'available')>
                                Available (copies on the shelf)
                            </option>
                            <option value="unavailable" @selected($filters['status'] === 'unavailable')>
                                Unavailable (none on the shelf)
                            </option>
                        </select>
                    </div>

                    
                    <div class="col-6">
                        <label for="filter_year_from" class="form-label">Year from</label>
                        <input type="number" name="year_from" id="filter_year_from"
                               value="{{ $filters['year_from'] }}" class="form-control"
                               placeholder="e.g. 1990" min="1000" max="{{ date('Y') }}" step="1">
                    </div>

                    <div class="col-6">
                        <label for="filter_year_to" class="form-label">Year to</label>
                        <input type="number" name="year_to" id="filter_year_to"
                               value="{{ $filters['year_to'] }}" class="form-control"
                               placeholder="e.g. {{ date('Y') }}" min="1000" max="{{ date('Y') }}" step="1">
                    </div>

                    <div class="col-12">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Books with an unknown publication year are hidden while a year range is set.
                        </small>
                    </div>

                    
                    <div class="col-12">
                        <label for="filter_sort" class="form-label">Sort by</label>
                        <select name="sort" id="filter_sort" class="form-select">
                            <option value="id" @selected($filters['sort'] === 'id')>Book ID (default)</option>
                            <option value="title_asc" @selected($filters['sort'] === 'title_asc')>Title (A → Z)</option>
                            <option value="title_desc" @selected($filters['sort'] === 'title_desc')>Title (Z → A)</option>
                            <option value="author_asc" @selected($filters['sort'] === 'author_asc')>Author (A → Z)</option>
                            <option value="year_desc" @selected($filters['sort'] === 'year_desc')>Year (newest first)</option>
                            <option value="year_asc" @selected($filters['sort'] === 'year_asc')>Year (oldest first)</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-0">
                <a href="{{ route('books', array_filter(['search' => request('search')])) }}"
                   class="btn btn-outline-secondary">Reset</a>
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-funnel me-1"></i> Apply Filters
                </button>
            </div>

        </form>

    </div>
</div>




<div class="modal fade" id="addBook" tabindex="-1" aria-hidden="true"
     data-show-on-load="{{ $errors->any() && ! $editFailed ? '1' : '0' }}">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <form method="POST" action="{{ route('books.store') }}" class="modal-content p-3">
            @csrf

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold">Add New Book</h5>
                    <small class="text-muted">Enter the book information below.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                @if ($errors->any() && ! $editFailed)
                    <div class="alert alert-danger">
                        <strong><i class="bi bi-exclamation-circle me-1"></i> Please check the following:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h6 class="fw-semibold mb-3">Book Information</h6>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="title" class="form-label">Book Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title"
                               value="{{ $editFailed ? '' : old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="Enter book title" maxlength="255" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                        <input type="text" id="author" name="author"
                               value="{{ $editFailed ? '' : old('author') }}"
                               class="form-control @error('author') is-invalid @enderror"
                               placeholder="Enter author name" maxlength="255" required>
                        @error('author')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="alert alert-warning py-2 mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Already in the catalog?</strong>
                            If this book already exists, update its quantity instead of creating another record.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id"
                                class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Select a category</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->category_id }}"
                                    @selected(! $editFailed && old('category_id') == $c->category_id)>
                                    {{ $c->category_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="publisher" class="form-label">Publisher</label>
                        <input type="text" name="publisher" id="publisher"
                               value="{{ $editFailed ? '' : old('publisher') }}"
                               class="form-control @error('publisher') is-invalid @enderror"
                               placeholder="Enter publisher" maxlength="255">
                        @error('publisher')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="publication_year" class="form-label">Publication Year</label>
                        <select name="publication_year" id="publication_year"
                                class="form-select @error('publication_year') is-invalid @enderror">
                            <option value="">Select a year</option>
                            @for ($y = date('Y'); $y >= 1000; $y--)
                                <option value="{{ $y }}"
                                    @selected(! $editFailed && old('publication_year') == $y)>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                        @error('publication_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <hr class="my-4">

                <h6 class="fw-semibold mb-3">Inventory Information</h6>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity"
                               value="{{ $editFailed ? 1 : old('quantity', 1) }}"
                               class="form-control @error('quantity') is-invalid @enderror"
                               min="1" max="100000" step="1" required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Total number of physical copies.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status"
                                class="form-select @error('status') is-invalid @enderror" required>
                            <option value="available"
                                @selected(($editFailed ? 'available' : old('status', 'available')) === 'available')>
                                Available
                            </option>
                            <option value="unavailable"
                                @selected(! $editFailed && old('status') === 'unavailable')>
                                Unavailable
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="alert alert-info mt-4 mb-0 py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Available quantity will automatically be initialized based on the selected status.
                </div>

            </div>

            <div class="modal-footer border-0">
                <button type="reset" class="btn btn-outline-secondary">Clear</button>
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-plus-circle me-1"></i> Add Book
                </button>
            </div>

        </form>

    </div>
</div>




<div class="modal fade" id="editBook" tabindex="-1" aria-labelledby="editBookLabel" aria-hidden="true"
     data-show-on-load="{{ $editFailed ? '1' : '0' }}">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <form method="POST" id="editBookForm"
              action="{{ $editFailed && old('edit_book_id') ? route('books.update', old('edit_book_id')) : '' }}"
              class="modal-content p-3">
            @csrf
            @method('PUT')

            <input type="hidden" name="edit_book_id" id="edit_book_id"
                   value="{{ $editFailed ? old('edit_book_id') : '' }}">

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold" id="editBookLabel">Edit Book</h5>
                    <small class="text-muted">Update the book information below.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                @if ($editFailed)
                    <div class="alert alert-danger" id="editErrors">
                        <strong><i class="bi bi-exclamation-circle me-1"></i> Please check the following:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->editBook->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h6 class="fw-semibold mb-3">Book Information</h6>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="edit_title" class="form-label">Book Title <span class="text-danger">*</span></label>
                        <input type="text" id="edit_title" name="title"
                               value="{{ $editFailed ? old('title') : '' }}"
                               class="form-control @if($errors->editBook->has('title')) is-invalid @endif"
                               maxlength="255" required>
                        @if ($errors->editBook->has('title'))
                            <div class="invalid-feedback">{{ $errors->editBook->first('title') }}</div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label for="edit_author" class="form-label">Author <span class="text-danger">*</span></label>
                        <input type="text" id="edit_author" name="author"
                               value="{{ $editFailed ? old('author') : '' }}"
                               class="form-control @if($errors->editBook->has('author')) is-invalid @endif"
                               maxlength="255" required>
                        @if ($errors->editBook->has('author'))
                            <div class="invalid-feedback">{{ $errors->editBook->first('author') }}</div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label for="edit_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="edit_category_id"
                                class="form-select @if($errors->editBook->has('category_id')) is-invalid @endif" required>
                            <option value="">Select a category</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->category_id }}"
                                    @selected($editFailed && old('category_id') == $c->category_id)>
                                    {{ $c->category_name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->editBook->has('category_id'))
                            <div class="invalid-feedback">{{ $errors->editBook->first('category_id') }}</div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label for="edit_publisher" class="form-label">Publisher</label>
                        <input type="text" name="publisher" id="edit_publisher"
                               value="{{ $editFailed ? old('publisher') : '' }}"
                               class="form-control @if($errors->editBook->has('publisher')) is-invalid @endif"
                               maxlength="255">
                        @if ($errors->editBook->has('publisher'))
                            <div class="invalid-feedback">{{ $errors->editBook->first('publisher') }}</div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label for="edit_publication_year" class="form-label">Publication Year</label>
                        <select name="publication_year" id="edit_publication_year"
                                class="form-select @if($errors->editBook->has('publication_year')) is-invalid @endif">
                            <option value="">Select a year</option>
                            @for ($y = date('Y'); $y >= 1000; $y--)
                                <option value="{{ $y }}"
                                    @selected($editFailed && old('publication_year') == $y)>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                        @if ($errors->editBook->has('publication_year'))
                            <div class="invalid-feedback">{{ $errors->editBook->first('publication_year') }}</div>
                        @endif
                    </div>

                </div>

                <hr class="my-4">

                <h6 class="fw-semibold mb-3">Inventory Information</h6>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="edit_quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="edit_quantity"
                               value="{{ $editFailed ? old('quantity') : '' }}"
                               class="form-control @if($errors->editBook->has('quantity')) is-invalid @endif"
                               min="1" max="100000" step="1" required>
                        @if ($errors->editBook->has('quantity'))
                            <div class="invalid-feedback">{{ $errors->editBook->first('quantity') }}</div>
                        @endif
                        <small class="text-muted" id="edit_borrowed_note">Total number of physical copies.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="edit_status"
                                class="form-select @if($errors->editBook->has('status')) is-invalid @endif" required>
                            <option value="available" @selected($editFailed && old('status') === 'available')>Available</option>
                            <option value="unavailable" @selected($editFailed && old('status') === 'unavailable')>Unavailable</option>
                        </select>
                        @if ($errors->editBook->has('status'))
                            <div class="invalid-feedback">{{ $errors->editBook->first('status') }}</div>
                        @endif
                    </div>

                </div>

                <div class="alert alert-info mt-4 mb-0 py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Available quantity is recalculated automatically. Copies that are currently borrowed are kept
                    out of the available count, and setting the status to Unavailable sets available copies to 0.
                </div>

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




<div class="modal fade" id="deleteBook" tabindex="-1" aria-labelledby="deleteBookLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold" id="deleteBookLabel">Delete Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Are you sure you want to delete this book?</p>
                <p class="fw-semibold mb-3" id="deleteBookTitle"></p>
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This action cannot be undone. Books with existing borrowing records cannot be deleted.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteBookForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete Book
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>




<div class="offcanvas offcanvas-end" tabindex="-1" id="viewBook" aria-labelledby="viewBookLabel">

    <div class="offcanvas-header">
        <div>
            <h6 class="m-0" id="v-id"></h6>
            <h3 id="v-title" class="text-info" aria-labelledby="viewBookLabel"></h3>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        
        <div class="bk-cover" id="v-cover-box">
            <img id="v-cover" alt="Book cover" hidden>
            <div id="v-cover-fallback" class="bk-cover-fallback">
                <i class="bi bi-book"></i>
            </div>
        </div>

        
        <div class="alert alert-light border small text-muted mb-4">
            <i class="bi bi-info-circle me-1"></i>
            <strong>About Book Covers</strong>
            <p class="mb-0 mt-1">
                Book cover images are automatically retrieved from the Open Library API when a matching book is
                found in its catalog. Cover availability depends on Open Library's database. If no matching cover
                is available, a default book icon will be displayed instead.
            </p>
        </div>

        
        <dl>
            <dt>Author</dt>
            <dd id="v-author"></dd>

            <dt>Publisher</dt>
            <dd id="v-publisher"></dd>

            <dt>Publication Date</dt>
            <dd id="v-year"></dd>

            <dt>Quantity</dt>
            <dd id="v-qty"></dd>

            <dt>Category</dt>
            <dd id="v-category"></dd>

            <dt>Status</dt>
            <dd id="v-status" class="fw-bold"></dd>
        </dl>

    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('books.js') }}"></script>
@endpush