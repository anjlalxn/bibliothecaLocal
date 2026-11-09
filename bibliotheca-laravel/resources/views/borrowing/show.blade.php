@extends('layouts.panel')

@section('title', 'Borrow Transaction')
@section('heading', session('created') ? 'Borrow Transaction Status' : 'Borrow Transaction')

@section('content')
@php
    $ids   = session('created_ids', [$borrowing->borrowing_id]);
    $tids  = collect($ids)->map(fn ($id) => 'B-' . str_pad($id, 3, '0', STR_PAD_LEFT));
    $badge = ['Returned' => 'success', 'Overdue' => 'danger', 'Borrowed' => 'info'][$borrowing->display_status];

    // Returned after the due date = returned late
    $returnedLate = $borrowing->return_date && $borrowing->due_date
        && $borrowing->return_date->toDateString() > $borrowing->due_date->toDateString();

    $daysLate = $returnedLate
        ? (int) $borrowing->due_date->diffInDays($borrowing->return_date)
        : 0;

    // Due date is red while the book is overdue or was returned late
    $dueIsRed = $returnedLate || $borrowing->display_status === 'Overdue';
@endphp

<div class="card p-4">
    <div class="row g-4 align-items-center">
        <div class="col-md-5 text-center">
            @if (session('created'))
                <i class="bi bi-check-circle-fill text-success" style="font-size: 7rem;"></i>
                <h2 class="text-success fw-bold">SUCCESS</h2>
                <p class="small text-muted">Transaction has been successfully completed.</p>
            @else
                <div id="book-cover-box"
                     data-title="{{ $borrowing->book->title ?? '' }}"
                     data-author="{{ $borrowing->book->author ?? '' }}">
                    <img id="book-cover-img"
                         alt="{{ $borrowing->book->title ?? 'Book cover' }}"
                         class="rounded mb-2"
                         style="display: none; max-height: 260px; max-width: 100%; object-fit: contain;">
                    <i id="book-cover-fallback" class="bi bi-journal-text text-info" style="font-size: 7rem;"></i>
                </div>
                <h2 class="fw-bold">
                    @if ($returnedLate)
                        <span class="badge text-bg-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>Returned Late
                        </span>
                    @else
                        <span class="badge text-bg-{{ $badge }}">{{ $borrowing->display_status }}</span>
                    @endif
                </h2>
            @endif
        </div>

        <div class="col-md-7">
            <h5 class="text-info">
                Borrow Transaction ID{{ $tids->count() > 1 ? 's' : '' }}: {{ $tids->join(', ') }}
            </h5>
            @if ($tids->count() > 1)
                <div class="text-muted small">{{ $tids->count() }} copies borrowed</div>
            @endif
            <div class="fw-semibold mt-3">Member</div>
            <div>{{ $borrowing->member->full_name ?? '—' }}</div>
            <div>Member Code: {{ $borrowing->member->member_code ?? '—' }}</div>

            <div class="fw-semibold mt-3">Book Details</div>
            <div>{{ $borrowing->book->title ?? '—' }}</div>
            <div>{{ $borrowing->book->author ?? '' }}</div>
            <div>{{ $borrowing->book->category->category_name ?? '' }}</div>
            <div>{{ $borrowing->book->publication_year ?? '' }}</div>

            <div class="row mt-3 g-3">
                <div class="col-sm-4">
                    <div class="fw-semibold">Borrow Date</div>
                    <div class="text-success">{{ $borrowing->borrowing_date?->format('F j, Y') }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="fw-semibold">Due Date</div>
                    <div class="{{ $dueIsRed ? 'text-danger' : '' }}">
                        {{ $borrowing->due_date?->format('F j, Y') }}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="fw-semibold">Return Date</div>
                    @if ($borrowing->return_date)
                        <div class="{{ $returnedLate ? 'text-warning-emphasis fw-semibold' : 'text-success' }}">
                            {{ $borrowing->return_date->format('F j, Y') }}
                        </div>
                    @else
                        <div class="text-muted">Not yet returned</div>
                    @endif
                </div>
            </div>

            @if ($returnedLate)
                <div class="alert alert-warning d-flex align-items-center gap-2 mt-3 mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Returned late.</strong>
                        This book came back {{ $daysLate }} day(s) after its due date.
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2 mt-4">
                @if (session('created'))
                    <a href="{{ url('/dashboard') }}" class="btn btn-outline-info">Go back Home</a>
                    <a href="{{ route('borrowing.create') }}" class="btn btn-success">Create another one</a>
                @else
                    <a href="{{ route('borrowing') }}" class="btn btn-outline-info">Back to Borrowing</a>
                @endif
            </div>
        </div>
    </div>
</div>

@unless (session('created'))
    <script src="{{ asset('borrow_info.js') }}" defer></script>
@endunless
@endsection