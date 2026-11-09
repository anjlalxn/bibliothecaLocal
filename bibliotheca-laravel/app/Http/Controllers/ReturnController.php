<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $from   = $request->query('from');
        $to     = $request->query('to');

        $borrowings = Borrowing::with(['member', 'book'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('member', function ($m) use ($search) {
                        $m->where('full_name', 'like', "%{$search}%")
                          ->orWhere('member_code', 'like', "%{$search}%");
                    })->orWhereHas('book', function ($b) use ($search) {
                        $b->where('title', 'like', "%{$search}%");
                    });
                });
            })
            // same rules the table badges use
            ->when($status === 'borrowed', fn ($q) => $q->whereNull('return_date')
                                                        ->whereDate('due_date', '>=', today()))
            ->when($status === 'due_today', fn ($q) => $q->whereNull('return_date')
                                                         ->whereDate('due_date', today()))
            ->when($status === 'overdue',  fn ($q) => $q->whereNull('return_date')
                                                        ->whereDate('due_date', '<', today()))
            ->when($status === 'returned', fn ($q) => $q->whereNotNull('return_date'))
            ->when($from, fn ($q) => $q->whereDate('borrowing_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('borrowing_date', '<=', $to))
            // newest borrowings first
            ->orderByDesc('borrowing_date')
            ->orderByDesc('borrowing_id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'dueToday'      => Borrowing::whereNull('return_date')
                                        ->whereDate('due_date', today())
                                        ->count(),
            'returnedToday' => Borrowing::whereDate('return_date', today())->count(),
            'active'        => Borrowing::whereNull('return_date')->count(),
            'overdue'       => Borrowing::whereNull('return_date')
                                        ->whereDate('due_date', '<', now())
                                        ->count(),
        ];

        return view('return.index', compact('borrowings', 'stats'));
    }

    public function store(Borrowing $borrowing)
    {
        // The return and the stock change are saved together or not at all
        $error = DB::transaction(function () use ($borrowing) {
            $record = Borrowing::lockForUpdate()->findOrFail($borrowing->borrowing_id);

            if ($record->return_date) {
                return 'This book was already returned.';
            }

            $record->update([
                'return_date' => today(),
                'status'      => 'returned',
            ]);

            $book = Book::lockForUpdate()->find($record->book_id);

            // never let available copies go above the total
            if ($book && $book->available_quantity < $book->quantity) {
                $book->increment('available_quantity');
            }

            return null;
        });

        if ($error) {
            return redirect()->route('return')->with('return_error', $error);
        }

        return redirect()->route('return')->with('return_success', 'Book marked as returned.');
    }
}