@extends('layouts.panel')

@section('title', $member->full_name)
@section('heading', 'Member Details')

@section('content')
@php
$isActive = strtolower((string) $member->status) === 'active';
$badgeMap = ['Returned' => 'success', 'Overdue' => 'danger', 'Borrowed' => 'info'];

// Collapse copies borrowed in the SAME transaction (same book, same
// borrow date, same due date, same status) into one card. Different
// days/months or different statuses stay as separate cards.
$grouped = $borrowings
->groupBy(fn ($b) =>
$b->book_id . '|' .
optional($b->borrowing_date)->toDateString() . '|' .
optional($b->due_date)->toDateString() . '|' .
$b->display_status
)
->map(fn ($group) => (object) [
'copies' => $group->count(),
'borrowing_id' => $group->first()->borrowing_id,
'book' => $group->first()->book,
'display_status' => $group->first()->display_status,
'borrowing_date' => $group->first()->borrowing_date,
'due_date' => $group->first()->due_date,
'return_date' => $group->first()->return_date,
]);
@endphp

<!-- Member info -->
<div class="card p-4 mb-4">
    <div class="row g-4 align-items-center">

        <div class="col-md-3 text-center">
            <i class="bi bi-person-circle text-info" style="font-size: 6rem;"></i>
            <div>
                @if ($isActive)
                <span class="badge text-bg-success fs-6">Active</span>
                @else
                <span class="badge text-bg-secondary fs-6">{{ ucfirst((string) $member->status) ?: 'Inactive' }}</span>
                @endif
            </div>
        </div>

        <div class="col-md-9">
            <h4 class="text-info mb-3">{{ $member->full_name }}</h4>

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="fw-semibold">Member Code</div>
                    <div>{{ $member->member_code }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="fw-semibold">Email</div>
                    <div>{{ $member->email }}</div>
                </div>
            </div>

            <hr>

            <div class="row text-center g-3">
                <div class="col-6 col-md-3">
                    <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
                    <div class="small text-muted">Total borrowed</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-3 fw-bold text-info">{{ $stats['active'] }}</div>
                    <div class="small text-muted">Currently borrowed</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-3 fw-bold text-danger">{{ $stats['overdue'] }}</div>
                    <div class="small text-muted">Overdue</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-3 fw-bold text-success">{{ $stats['returned'] }}</div>
                    <div class="small text-muted">Returned</div>
                </div>
            </div>

            <!-- Updated Buttons Section -->
            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('members') }}" class="btn btn-outline-info">Back to Members</a>
                
                <!-- New Pink Email Button -->
                <a href="mailto:{{ $member->email }}" class="btn text-white" style="background-color: #e83e8c; border-color: #e83e8c;">
                    <i class="bi bi-envelope-fill me-1"></i> Email Member
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Borrowed books grid -->
<section class="section-block">
    <h2 class="section-title mb-3">Borrowed Books</h2>

    @if ($grouped->isEmpty())
    <div class="card p-4 text-center text-muted">
        <i class="bi bi-inbox fs-1"></i>
        <div>This member hasn't borrowed any books yet.</div>
    </div>
    @else
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3">
        @foreach ($grouped as $entry)
        @php
        $status = $entry->display_status;
        $badge = $badgeMap[$status] ?? 'secondary';
        @endphp
        <div class="col">
            <a href="{{ route('borrowing.show', $entry->borrowing_id) }}"
                class="text-decoration-none text-reset">
                <div class="card h-100 border rounded-3 overflow-hidden member-book-card"
                    data-title="{{ $entry->book->title ?? '' }}"
                    data-author="{{ $entry->book->author ?? '' }}">

                    <div class="bg-light d-flex align-items-center justify-content-center border-bottom position-relative"
                        style="aspect-ratio: 2/3;">
                        <img class="book-cover-img w-100 h-100"
                            style="object-fit: cover; display: none;"
                            alt="{{ $entry->book->title ?? 'Book cover' }}">
                        <div class="book-cover-fallback">
                            <i class="bi bi-book text-info fs-1"></i>
                        </div>

                        @if ($entry->copies > 1)
                        <span class="badge text-bg-dark position-absolute top-0 end-0 m-1">
                            ×{{ $entry->copies }}
                        </span>
                        @endif
                    </div>

                    <div class="card-body p-2 text-center">
                        <h6 class="card-title mb-1 text-truncate" title="{{ $entry->book->title ?? '—' }}">
                            {{ $entry->book->title ?? '—' }}
                        </h6>
                        <p class="card-text small text-muted mb-1 text-truncate"
                            title="{{ $entry->book->author ?? '' }}">
                            {{ $entry->book->author ?? '' }}
                        </p>

                        <div class="small text-muted">
                            <i class="bi bi-calendar-event me-1"></i>
                            Borrowed {{ optional($entry->borrowing_date)->format('M j, Y') ?? '—' }}
                        </div>
                        <div class="small {{ $status === 'Overdue' ? 'text-danger' : 'text-muted' }}">
                            <i class="bi bi-calendar-x me-1"></i>
                            Due {{ optional($entry->due_date)->format('M j, Y') ?? '—' }}
                        </div>

                        <span class="badge text-bg-{{ $badge }} mt-2">{{ $status }}</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    @endif
</section>

@endsection

@push('scripts')
<script src="{{ asset('profile.js') }}"></script>
@endpush