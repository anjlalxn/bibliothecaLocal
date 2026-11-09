<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;

class DashboardController extends Controller
{
    public function index()
    {
        $search = trim((string) request('search', ''));

        $books = Book::with('category')   // loads each book's category in one extra query
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('publisher', 'like', "%{$search}%")
                      ->orWhereHas('category', function ($c) use ($search) {
                          $c->where('category_name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('book_id')
            ->paginate(15)
            ->withQueryString();          // keeps ?search=... when you change pages

        $stats = [
            'total'     => Book::sum('quantity'),
            'available' => Book::sum('available_quantity'),
            'borrowed'  => Borrowing::whereNull('return_date')->count(),
            'overdue'   => Borrowing::whereNull('return_date')
                                    ->whereDate('due_date', '<', now())
                                    ->count(),
        ];

        return view('dashboard', compact('books', 'stats', 'search'));
    }
}