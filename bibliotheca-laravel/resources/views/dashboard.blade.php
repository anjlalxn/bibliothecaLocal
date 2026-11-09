@extends('layouts.panel')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')


<div class="stat-panel">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
        <div class="col">
            <a href="{{ route('books') }}" class="stat-card stat-card--teal">
                <i class="bi bi-bookshelf stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Total Books (Copies)</p>
                    <p class="stat-value">{{ number_format($stats['total']) }}</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('books') }}" class="stat-card stat-card--cyan">
                <i class="bi bi-check-square stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Available Books</p>
                    <p class="stat-value">{{ number_format($stats['available']) }}</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('borrowing') }}" class="stat-card stat-card--light">
                <i class="bi bi-clock stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Active Borrowings</p>
                    <p class="stat-value">{{ number_format($stats['borrowed']) }}</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('return') }}" class="stat-card stat-card--black">
                <i class="bi bi-exclamation-circle stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Overdue Books</p>
                    <p class="stat-value">{{ number_format($stats['overdue']) }}</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        <div class="col">
            {{-- Opens the transaction search modal instead of going straight to Borrowing --}}
            <a href="#" class="stat-card stat-card--dark-gray" data-bs-toggle="modal" data-bs-target="#transactionSearchModal">
                <i class="bi bi-envelope-open stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Transaction Details</p>
                    <p class="stat-value">View a transaction</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('borrowing') }}" class="stat-card stat-card--gray">
                <i class="bi bi-clock-history stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Borrow History</p>
                    <p class="stat-value">View history</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
    </div>
</div>

@php
$booksOfTheDay = \Illuminate\Support\Facades\Cache::remember('books_of_the_day', now()->endOfDay(), function () {
return \App\Models\Book::where('status', 'available')->inRandomOrder()->take(5)->get();
});
@endphp


<section class="section-block mt-4 mb-4">
    <h2 class="section-title mb-3">Books of the Day</h2>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3">
        @foreach ($booksOfTheDay as $book)
        <div class="col">
            <div class="card h-100 border book-day-card rounded-3 overflow-hidden" data-title="{{ $book->title }}" data-author="{{ $book->author }}">
                <div class="bg-light d-flex align-items-center justify-content-center border-bottom" style="aspect-ratio: 2/3;">
                    <img class="book-cover-img w-100 h-100" style="object-fit: cover; display: none;" alt="{{ $book->title }}">
                    <div class="book-cover-fallback">
                        <i class="bi bi-book text-info fs-1"></i>
                    </div>
                </div>
                <div class="card-body p-2 text-center d-flex flex-column justify-content-center">
                    <h6 class="card-title mb-1 text-truncate" title="{{ $book->title }}">{{ $book->title }}</h6>
                    <p class="card-text small text-muted mb-0 text-truncate" title="{{ $book->author }}">{{ $book->author }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>


<section class="section-block">
    <div class="d-flex flex-column-reverse flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div class="search-toolbar d-flex align-items-center gap-2">
            <form class="search-group input-group" action="{{ route('dashboard') }}" method="GET" role="search">
                <input type="search" class="form-control" name="search" value="{{ $search ?? '' }}" placeholder="Author/Category/Book" aria-label="Search books">
                <button class="btn btn-info text-white" type="submit">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </form>

        </div>
        <div class="text-sm-end">
            <h2 class="section-title">All Books</h2>
        </div>
    </div>

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
                </tr>
            </thead>
            <tbody>
                @forelse ($books as $book)
                @php
                $isAvailable = $book->available_quantity > 0
                && strtolower((string) $book->status) === 'available';
                @endphp
                <tr>
                    <td>BOOK-{{ $book->book_id }}</td>
                    <td>{{ $book->category->category_name ?? '—' }}</td>
                    <td>{{ $book->title }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->publisher }}</td>
                    <td>{{ $book->publication_year ?: 'Unknown Year' }}</td>
                    <td>{{ $book->quantity }}</td>
                    <td>{{ $book->available_quantity }}</td>
                    <td>
                        @if ($isAvailable)
                        <span class="badge text-bg-success">Available</span>
                        @else
                        <span class="badge text-bg-danger">Unavailable</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="table-empty">
                        <i class="bi bi-inbox"></i>
                        @if (isset($search) && $search !== '')
                        No author, category, or books match "{{ $search }}".
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


{{-- Transaction search modal: sends the typed search to the Borrowing page (?search=...) --}}
<div class="modal fade" id="transactionSearchModal" tabindex="-1" aria-labelledby="transactionSearchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" action="{{ route('borrowing') }}" class="modal-content p-3" role="search">

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold" id="transactionSearchLabel">Find a Transaction</h5>
                    <small class="text-muted">Search by member name, member code, or book title.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <label for="transaction_search" class="form-label">Search</label>
                <div class="input-group">
                    <input type="search" id="transaction_search" name="search"
                        class="form-control"
                        placeholder="e.g. Juan Dela Cruz, MEM-001, Little Princess"
                        maxlength="255" required autocomplete="off">
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('dashboard.js') }}"></script>
@endpush