@extends('layouts.panel')

@push('styles')
<link href="{{ asset('analytics.css') }}" rel="stylesheet">
@endpush

@section('title', 'Analytics')
@section('heading', 'Analytics')

@section('content')

<div class="analytics">


    
    <div class="stat-panel mb-4">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">

            <div class="col">
                <div class="stat-card an-card--deep-teal">
                    <i class="bi bi-book stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Total Books</p>
                        <p class="stat-value">{{ number_format($totalBooks) }}</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="stat-card an-card--cyan">
                    <i class="bi bi-people stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Members</p>
                        <p class="stat-value">{{ number_format($totalMembers) }}</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="stat-card an-card--light-cyan">
                    <i class="bi bi-arrow-left-right stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Active Loans</p>
                        <p class="stat-value">{{ number_format($activeLoans) }}</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="stat-card an-card--black">
                    <i class="bi bi-exclamation-triangle stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Overdue</p>
                        <p class="stat-value">{{ number_format($overdue) }}</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="stat-card an-card--deep-teal">
                    <i class="bi bi-journal-bookmark stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Total Borrowings</p>
                        <p class="stat-value">{{ number_format($totalBorrowings) }}</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="stat-card an-card--cyan">
                    <i class="bi bi-check2-square stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Available Copies</p>
                        <p class="stat-value">{{ number_format($availableCopies) }}</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="stat-card an-card--light-cyan">
                    <i class="bi bi-arrow-return-left stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Return Rate</p>
                        <p class="stat-value">{{ $returnRate }}%</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="stat-card an-card--sand">
                    <i class="bi bi-hourglass-split stat-icon"></i>
                    <div class="stat-body">
                        <p class="stat-label">Avg Loan (days)</p>
                        <p class="stat-value">{{ $avgLoanDays }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    
    <div class="row g-3 mb-3">

        <div class="col-lg-8">
            <div class="a-card">
                <h2>Borrowings Over Time</h2>
                <p class="a-sub">Books borrowed per month, last 6 months</p>
                <div class="chart-box">
                    <canvas id="monthChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="a-card">
                <h2>Borrowing Status</h2>
                <p class="a-sub">Borrowed, returned and overdue</p>
                <div class="chart-box">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    
    <div class="row g-3 mb-3">

        <div class="col-lg-6">
            <div class="a-card">
                <h2>Most Popular Books</h2>
                <p class="a-sub">Top 8 by number of times borrowed</p>

                @if ($topBooks->count())
                @php $maxBorrows = $topBooks->max('borrowings_count'); @endphp

                <ol class="rank-list">
                    @foreach ($topBooks as $book)
                    <li class="rank-row">
                        <span class="rank-num">{{ $loop->iteration }}</span>

                        <div class="rank-cover"
                            data-title="{{ $book->title }}"
                            data-author="{{ $book->author }}">
                            <img alt="">
                            <i class="bi bi-book"></i>
                        </div>

                        <div class="rank-info">
                            <p class="rank-title" title="{{ $book->title }}">{{ $book->title }}</p>
                            <p class="rank-author">{{ $book->author }}</p>
                            <div class="rank-track">
                                <div class="rank-fill"
                                    style="width: {{ ($book->borrowings_count / $maxBorrows) * 100 }}%"></div>
                            </div>
                        </div>

                        <span class="rank-count">{{ $book->borrowings_count }}x</span>
                    </li>
                    @endforeach
                </ol>
                @else
                <div class="a-empty">No borrowings yet.</div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="a-card">
                <h2>Top Borrowers</h2>
                <p class="a-sub">Members who borrowed the most books</p>
                <div class="chart-box">
                    @if (count($memberData))
                    <canvas id="memberChart"></canvas>
                    @else
                    <div class="a-empty">No borrowings yet.</div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    
    <div class="row g-3 mb-3">

        <div class="col-lg-5">
            <div class="a-card">
                <h2>Books by Category</h2>
                <p class="a-sub">Top 6 categories, the rest grouped as Others</p>
                <div class="chart-box chart-box--sm">
                    @if (count($categoryData))
                    <canvas id="categoryChart"></canvas>
                    @else
                    <div class="a-empty">No categories yet.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="a-card">
                <h2>Busiest Days</h2>
                <p class="a-sub">Which weekdays books get borrowed the most</p>
                <div class="chart-box chart-box--sm">
                    <canvas id="weekdayChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    
    <div class="row g-3">
        <div class="col-12">
            <div class="a-card">
                <h2>Currently Overdue</h2>
                <p class="a-sub">The 5 loans that are the most late</p>

                @if ($overdueLoans->count())
                <div class="table-responsive">
                    <table class="an-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Book</th>
                                <th>Due Date</th>
                                <th>Late By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overdueLoans as $loan)
                            <tr>
                                <td>{{ $loan->member->full_name ?? 'Unknown' }}</td>
                                <td>{{ $loan->book->title ?? 'Unknown' }}</td>
                                <td>{{ $loan->due_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="late-pill">
                                        {{ abs((int) $loan->due_date->diffInDays(today())) }} day(s)
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="a-empty">Nothing overdue. Great job!</div>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Data from Laravel (must stay in the Blade file)
    window.analyticsData = {
        monthLabels: @json($monthLabels),
        monthData: @json($monthData),
        statusData: @json($statusData),
        categoryLabels: @json($categoryLabels),
        categoryData: @json($categoryData),
        memberLabels: @json($memberLabels),
        memberData: @json($memberData),
        weekdayLabels: @json($weekdayLabels),
        weekdayData: @json($weekdayData)
    };
</script>
<script src="{{ asset('analytics.js') }}"></script>
@endpush