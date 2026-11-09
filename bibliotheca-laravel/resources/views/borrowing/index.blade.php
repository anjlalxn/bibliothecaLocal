@extends('layouts.panel')

@section('title', 'Borrowing')
@section('heading', 'Borrowing')

@section('content')
<div class="stat-panel">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
        <div class="col">
            <div class="stat-card stat-card--teal">
                <i class="bi bi-journal-text stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Active Borrowings</p>
                    <p class="stat-value">{{ $stats['active'] }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card stat-card--cyan">
                <i class="bi bi-check-square stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Borrowed Today</p>
                    <p class="stat-value">{{ $stats['borrowedToday'] }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card stat-card--black">
                <i class="bi bi-exclamation-circle stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Overdue</p>
                    <p class="stat-value">{{ $stats['overdue'] }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card stat-card--light-gray">
                <i class="bi bi-arrow-counterclockwise stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Returned Today</p>
                    <p class="stat-value">{{ $stats['returnedToday'] }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <a href="{{ route('books') }}" class="stat-card stat-card--gray">
                <i class="bi bi-book stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Available Books</p>
                    <p class="stat-value">{{ number_format($stats['available']) }}</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('borrowing.create') }}" class="stat-card stat-card--outline">
                <i class="bi bi-envelope-open stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">New Borrow Transaction</p>
                    <p class="stat-value">Create</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
    </div>
</div>

<section class="section-block">
    <div class="d-flex flex-column-reverse flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div class="search-toolbar d-flex align-items-center gap-2">
            <form class="search-group input-group" action="{{ route('borrowing') }}" method="GET" role="search">
                <input type="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search..." aria-label="Search borrowings">
                <button class="btn btn-info text-white" type="submit">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </form>

            <button class="btn btn-filter" type="button" data-bs-toggle="modal"
                data-bs-target="#filterModal" aria-label="Filter">
                <i class="bi bi-filter"></i>
            </button>

            <a href="{{ route('borrowing.export', request()->query()) }}"
                class="btn btn-sm btn-outline-secondary" title="Download as CSV">
                <i class="bi bi-download"></i>
            </a>

            @if (request()->hasAny(['status', 'from', 'to']))
            <a href="{{ route('borrowing', array_filter(['search' => request('search')])) }}"
                class="btn btn-sm btn-outline-secondary">Clear filters</a>
            @endif
        </div>
        <div class="text-sm-end">
            <h2 class="section-title">Borrowing</h2>
            <p class="section-subtitle">Member Transactions</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th scope="col">Member</th>
                    <th scope="col">Book</th>
                    <th scope="col">Date Borrowed</th>
                    <th scope="col">Due Date</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($borrowings as $b)
                @php
                $badge = ['Returned' => 'success', 'Overdue' => 'danger', 'Borrowed' => 'info'][$b->display_status];

                // Reminder email (opens a Gmail draft) for this borrowing
                $memberEmail = $b->member->email ?? null;
                $bookTitle = $b->book->title ?? 'Unknown book';
                $dueText = $b->due_date?->format('F j, Y') ?? '-';
                $isOverdue = $b->display_status === 'Overdue';

                $remindSubject = $isOverdue
                ? 'Bibliotheca | Overdue book notice'
                : 'Bibliotheca | Reminder: your book is due soon';

                $remindBody = "Hello " . ($b->member->full_name ?? '') . ",\n\n"
                . ($isOverdue
                ? "The book \"{$bookTitle}\" was due on {$dueText} and has not been returned yet. Please return it as soon as you can."
                : "This is a reminder that the book \"{$bookTitle}\" is due on {$dueText}. Please return it on or before that date.")
                . "\n\nThank you.\nBibliotheca";

                $remindLink = $memberEmail
                ? 'https://mail.google.com/mail/?view=cm&to=' . rawurlencode($memberEmail)
                . '&su=' . rawurlencode($remindSubject)
                . '&body=' . rawurlencode($remindBody)
                : '#';
                @endphp
                <tr>
                    <td>
                        {{ $b->member->full_name ?? '—' }}
                        <div class="text-muted small">{{ $b->member->member_code ?? '' }}</div>
                    </td>
                    <td>{{ $b->book->title ?? '—' }}</td>
                    <td>{{ $b->borrowing_date?->format('M d, Y') }}</td>
                    <td>{{ $b->due_date?->format('M d, Y') }}</td>
                    <td><span class="badge text-bg-{{ $badge }}">{{ $b->display_status }}</span></td>
                    <td class="text-nowrap">
                        <a href="{{ route('borrowing.show', $b->borrowing_id) }}"
                            class="btn btn-sm btn-outline-primary" aria-label="View">
                            <i class="bi bi-eye"></i>
                        </a>

                        @if ($b->display_status !== 'Returned')
                        <a href="{{ $remindLink }}"
                            target="_blank" rel="noopener"
                            class="btn btn-sm btn-outline-warning remind-bell {{ $remindLink === '#' ? 'disabled' : '' }}"
                            data-key="reminded-borrowing-{{ $b->borrowing_id }}-{{ $b->display_status }}"
                            title="{{ $memberEmail ? 'Email a reminder to the member' : 'This member has no email' }}"
                            @if ($remindLink==='#' ) aria-disabled="true" tabindex="-1" @endif
                            aria-label="Send reminder">
                            <i class="bi bi-bell"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="table-empty">
                        <i class="bi bi-inbox"></i>
                        No borrowing records yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $borrowings->links() }}
</section>

{{-- Filter modal --}}
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" action="{{ route('borrowing') }}" class="modal-content p-3">
            <h5 class="mb-3">Filter borrowings</h5>

            {{-- keep the current search when filtering --}}
            <input type="hidden" name="search" value="{{ request('search') }}">

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="borrowed" @selected(request('status')==='borrowed' )>Borrowed</option>
                    <option value="overdue" @selected(request('status')==='overdue' )>Overdue</option>
                    <option value="returned" @selected(request('status')==='returned' )>Returned</option>
                </select>
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label">Borrowed from</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label">Borrowed to</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button class="btn btn-info text-white">Apply</button>
                <a href="{{ route('borrowing') }}" class="btn btn-secondary">Reset</a>
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('reminder.js') }}"></script>
@endpush