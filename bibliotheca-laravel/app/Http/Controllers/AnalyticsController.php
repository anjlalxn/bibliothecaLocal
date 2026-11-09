<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Member;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today = today();

        // ---------- Stat cards (row 1) ----------
        $totalBooks   = Book::count();
        $totalMembers = Member::count();

        $activeLoans = Borrowing::whereNull('return_date')->count();

        $overdue = Borrowing::whereNull('return_date')
            ->whereDate('due_date', '<', $today)
            ->count();

        $returned = Borrowing::whereNotNull('return_date')->count();
        $onLoan   = $activeLoans - $overdue; // borrowed, not yet due

        // ---------- Stat cards (row 2) ----------
        $totalBorrowings = Borrowing::count();
        $totalCopies     = (int) Book::sum('quantity');
        $availableCopies = (int) Book::sum('available_quantity');

        $returnRate = $totalBorrowings > 0
            ? round(($returned / $totalBorrowings) * 100)
            : 0;

        // One query for everything date-based
        $allBorrowings = Borrowing::get(['borrowing_date', 'return_date']);

        // Average days a book stays out (returned loans only)
        $returnedLoans = $allBorrowings->filter(fn ($b) => $b->return_date && $b->borrowing_date);

        $avgLoanDays = $returnedLoans->count() > 0
            ? round($returnedLoans->avg(fn ($b) => abs($b->borrowing_date->diffInDays($b->return_date))), 1)
            : 0;

        // ---------- Borrowings per month (last 6 months) ----------
        $start = $today->copy()->startOfMonth()->subMonths(5);

        $perMonth = $allBorrowings
            ->filter(fn ($b) => $b->borrowing_date && $b->borrowing_date->gte($start))
            ->groupBy(fn ($b) => $b->borrowing_date->format('Y-m'))
            ->map->count();

        $monthLabels = [];
        $monthData   = [];

        for ($i = 0; $i < 6; $i++) {
            $month         = $start->copy()->addMonths($i);
            $monthLabels[] = $month->format('M Y');
            $monthData[]   = $perMonth[$month->format('Y-m')] ?? 0;
        }

        // ---------- Borrowings by weekday ----------
        $perWeekday = $allBorrowings
            ->filter(fn ($b) => $b->borrowing_date)
            ->groupBy(fn ($b) => $b->borrowing_date->dayOfWeek) // 0 = Sunday
            ->map->count();

        $weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $weekdayData   = [];

        for ($d = 0; $d < 7; $d++) {
            $weekdayData[] = $perWeekday[$d] ?? 0;
        }

        // ---------- Books by category (top 6 + Others) ----------
        $categories = Category::withCount('books')
            ->orderByDesc('books_count')
            ->get();

        $topCategories  = $categories->take(6);
        $othersCount    = $categories->slice(6)->sum('books_count');

        $categoryLabels = $topCategories->pluck('category_name')->values()->all();
        $categoryData   = $topCategories->pluck('books_count')->values()->all();

        if ($othersCount > 0) {
            $categoryLabels[] = 'Others';
            $categoryData[]   = (int) $othersCount;
        }

        // ---------- Most popular books (top 8) ----------
        $topBooks = Book::withCount('borrowings')
            ->having('borrowings_count', '>', 0)
            ->orderByDesc('borrowings_count')
            ->take(8)
            ->get();

        // ---------- Top borrowers (top 5 members) ----------
        $topMembers = Member::withCount('borrowings')
            ->having('borrowings_count', '>', 0)
            ->orderByDesc('borrowings_count')
            ->take(5)
            ->get();

        $memberLabels = $topMembers->pluck('full_name')->values()->all();
        $memberData   = $topMembers->pluck('borrowings_count')->values()->all();

        // ---------- Currently overdue (5 oldest) ----------
        $overdueLoans = Borrowing::with(['member', 'book'])
            ->whereNull('return_date')
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date')
            ->take(5)
            ->get();

        return view('analytics', [
            'totalBooks'      => $totalBooks,
            'totalMembers'    => $totalMembers,
            'activeLoans'     => $activeLoans,
            'overdue'         => $overdue,
            'totalBorrowings' => $totalBorrowings,
            'availableCopies' => $availableCopies,
            'totalCopies'     => $totalCopies,
            'returnRate'      => $returnRate,
            'avgLoanDays'     => $avgLoanDays,
            'statusData'      => [$onLoan, $returned, $overdue],
            'monthLabels'     => $monthLabels,
            'monthData'       => $monthData,
            'weekdayLabels'   => $weekdayLabels,
            'weekdayData'     => $weekdayData,
            'categoryLabels'  => $categoryLabels,
            'categoryData'    => $categoryData,
            'topBooks'        => $topBooks,
            'memberLabels'    => $memberLabels,
            'memberData'      => $memberData,
            'overdueLoans'    => $overdueLoans,
        ]);
    }
}