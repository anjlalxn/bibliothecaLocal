@extends('layouts.panel')

@section('title', 'Return')
@section('heading', 'Return')

@push('styles')
    <link rel="stylesheet" href="{{ asset('return.css') }}">
@endpush

@section('content')
    @php
        // Filter keys that need to survive a search submit
        $filterKeys = ['status', 'from', 'to'];

        // How many filters are active (shown on the filter button)
        $activeFilterCount = collect($filterKeys)->filter(fn ($k) => request()->filled($k))->count();

        // Popup message (success or error), if any
        $popupSuccess = session('return_success');
        $popupError = session('return_error');
    @endphp


    <div class="stat-panel">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
            <div class="col">
                <div class="stat-card stat-card--teal">
                    <i class="bi bi-calendar-event stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Due Today</p>
                        <p class="stat-value">{{ $stats['dueToday'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card stat-card--cyan">
                    <i class="bi bi-check-square stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Returned Today</p>
                        <p class="stat-value">{{ $stats['returnedToday'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card stat-card--light">
                    <i class="bi bi-clock stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Active Borrowings</p>
                        <p class="stat-value">{{ $stats['active'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card stat-card--black">
                    <i class="bi bi-exclamation-circle stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Overdue Books</p>
                        <p class="stat-value">{{ $stats['overdue'] }}</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <a href="{{ route('borrowing') }}" class="stat-card stat-card--gray">
                    <i class="bi bi-clock-history stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Check Borrows</p>
                        <p class="stat-value">View history</p>
                    </div>
                    <i class="bi bi-chevron-right stat-arrow"></i>
                </a>
            </div>
        </div>
    </div>


    <section class="section-block">

        <div class="d-flex flex-column-reverse flex-sm-row justify-content-sm-between align-items-sm-center gap-3">

            <div class="search-toolbar d-flex align-items-center gap-2">

                <form class="search-group input-group" action="{{ route('return') }}" method="GET" role="search">

                    {{-- Keep active filters when searching --}}
                    @foreach ($filterKeys as $key)
                        @if (request()->filled($key))
                            <input type="hidden" name="{{ $key }}" value="{{ request($key) }}">
                        @endif
                    @endforeach

                    <input type="search" class="form-control" name="search" value="{{ request('search') }}"
                           placeholder="Member/Book" aria-label="Search returns">

                    <button class="btn btn-info text-white" type="submit">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </form>

                <button class="btn btn-filter" type="button" data-bs-toggle="modal"
                        data-bs-target="#filterReturns" aria-label="Filter">
                    <i class="bi bi-filter"></i>
                    @if ($activeFilterCount > 0)
                        <span class="filter-badge">{{ $activeFilterCount }}</span>
                    @endif
                </button>

            </div>

            <div class="text-sm-end">
                <h2 class="section-title">Returns</h2>
            </div>

        </div>


        @if ($activeFilterCount > 0)
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small text-muted">
        <span>
            <i class="bi bi-funnel me-1"></i>
            {{ $borrowings->total() }}
            {{ \Illuminate\Support\Str::plural('result', $borrowings->total()) }}
            with {{ $activeFilterCount }}
            {{ \Illuminate\Support\Str::plural('filter', $activeFilterCount) }}
            applied
        </span>
                <a href="{{ route('return', array_filter(['search' => request('search')])) }}"
                   class="link-danger text-decoration-none">
                    <i class="bi bi-x-circle me-1"></i>Clear filters
                </a>
            </div>
        @endif


        <div class="table-responsive">
            <table class="table data-table">

                <thead>
                <tr>
                    <th scope="col">Transaction ID</th>
                    <th scope="col">Member</th>
                    <th scope="col">Book</th>
                    <th scope="col">Borrow Date</th>
                    <th scope="col">Due Date</th>
                    <th scope="col">Return Date</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($borrowings as $b)
                    @php
                        $badge = ['Returned' => 'success', 'Overdue' => 'danger', 'Borrowed' => 'info'][$b->display_status];

                        // Returned after the due date = returned late
                        $returnedLate = $b->return_date && $b->due_date
                        && $b->return_date->toDateString() > $b->due_date->toDateString();

                        $daysLate = $returnedLate
                        ? (int) $b->due_date->diffInDays($b->return_date)
                        : 0;
                    @endphp

                    <tr>
                        <td class="text-nowrap fw-semibold">
                            B-{{ str_pad($b->borrowing_id, 3, '0', STR_PAD_LEFT) }}
                        </td>
                        <td>
                            {{ $b->member->full_name ?? '—' }}
                            <div class="text-muted small">{{ $b->member->member_code ?? '' }}</div>
                        </td>
                        <td>{{ $b->book->title ?? '—' }}</td>
                        <td>{{ $b->borrowing_date?->format('M d, Y') }}</td>
                        <td>{{ $b->due_date?->format('M d, Y') }}</td>
                        <td>
                            @if ($b->return_date)
                                <span class="{{ $returnedLate ? 'fw-semibold text-warning-emphasis' : 'text-success' }}">
                            {{ $b->return_date->format('M d, Y') }}
                        </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($returnedLate)
                                <span class="badge text-bg-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>Returned Late
                        </span>
                                <div class="text-warning-emphasis small">{{ $daysLate }} day(s) late</div>
                            @else
                                <span class="badge text-bg-{{ $badge }}">{{ $b->display_status }}</span>
                                @if ($b->display_status === 'Overdue')
                                    <div class="text-danger small">{{ (int) $b->due_date->diffInDays(today()) }} day(s) late</div>
                                @endif
                            @endif
                        </td>

                        <td class="text-nowrap">


                            @unless ($b->return_date)
                                <button type="button"
                                        class="btn btn-sm btn-outline-success return-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmReturn"
                                        data-url="{{ route('return.store', $b->borrowing_id) }}"
                                        data-book="{{ $b->book->title ?? '—' }}"
                                        data-member="{{ $b->member->full_name ?? '—' }}"
                                        title="Mark as returned"
                                        aria-label="Mark as returned">
                                    <i class="bi bi-arrow-return-left"></i>
                                </button>
                            @endunless


                            <a href="{{ route('borrowing.show', $b->borrowing_id) }}"
                               class="btn btn-sm btn-outline-primary"
                               title="View" aria-label="View">
                                <i class="bi bi-eye"></i>
                            </a>

                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="8" class="table-empty">
                            <i class="bi bi-inbox"></i>
                            @if (request('search') || $activeFilterCount > 0)
                                No records match your
                                @if (request('search'))
                                    search "{{ request('search') }}"
                                    @if ($activeFilterCount > 0) and @endif
                                @endif
                                @if ($activeFilterCount > 0)
                                    filters
                                @endif.
                            @else
                                No records found.
                            @endif
                        </td>
                    </tr>

                @endforelse
                </tbody>

            </table>
        </div>


        <div class="mt-3">
            {{ $borrowings->links() }}
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




    <div class="modal fade" id="filterReturns" tabindex="-1" aria-labelledby="filterReturnsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">

            <form method="GET" action="{{ route('return') }}" class="modal-content p-3">

                {{-- Keep the current search term when filtering --}}
                @if (request()->filled('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title fw-semibold" id="filterReturnsLabel">Filter Returns</h5>
                        <small class="text-muted">Narrow down the list. Filters work together with search.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">


                        <div class="col-12">
                            <label for="filter_status" class="form-label">Status</label>
                            <select name="status" id="filter_status" class="form-select">
                                <option value="">Any status</option>
                                <option value="borrowed" @selected(request('status')==='borrowed' )>Borrowed (not yet due)</option>
                                <option value="due_today" @selected(request('status')==='due_today' )>Due today</option>
                                <option value="overdue" @selected(request('status')==='overdue' )>Overdue</option>
                                <option value="returned" @selected(request('status')==='returned' )>Returned</option>
                            </select>
                        </div>


                        <div class="col-6">
                            <label for="filter_from" class="form-label">Borrowed from</label>
                            <input type="date" name="from" id="filter_from"
                                   value="{{ request('from') }}" class="form-control">
                        </div>

                        <div class="col-6">
                            <label for="filter_to" class="form-label">Borrowed to</label>
                            <input type="date" name="to" id="filter_to"
                                   value="{{ request('to') }}" class="form-control">
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-0">
                    <a href="{{ route('return', array_filter(['search' => request('search')])) }}"
                       class="btn btn-outline-secondary">Reset</a>
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-funnel me-1"></i> Apply Filters
                    </button>
                </div>

            </form>

        </div>
    </div>




    <div class="modal fade" id="confirmReturn" tabindex="-1" aria-labelledby="confirmReturnLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold" id="confirmReturnLabel">Return Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Mark this book as returned?</p>
                    <p class="fw-semibold mb-1" id="returnBookTitle"></p>
                    <p class="text-muted mb-3">Borrowed by <span id="returnMemberName"></span></p>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        The return date will be set to today and the book's available copies will be updated.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="returnForm" method="POST" action="">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-arrow-return-left me-1"></i> Confirm Return
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('return.js') }}"></script>
@endpush