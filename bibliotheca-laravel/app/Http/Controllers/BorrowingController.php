<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BorrowingController extends Controller
{
    private const LOAN_DAYS = 10;
    private const MAX_COPIES = 5;

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $from   = $request->query('from');
        $to     = $request->query('to');

        // One row per transaction: copies borrowed together (same member, book,
        // borrow date, due date, and returned/not returned) are collapsed.
        // borrowing_id is the lowest ID in the group, so the "view" link opens
        // the same page the member page cards open.
        $borrowings = Borrowing::with(['member', 'book'])
            ->select([
                DB::raw('MIN(borrowing_id) as borrowing_id'),
                'member_id',
                'book_id',
                DB::raw('MIN(borrowing_date) as borrowing_date'),
                DB::raw('MIN(due_date) as due_date'),
                DB::raw('MAX(return_date) as return_date'),
                DB::raw('COUNT(*) as copies'),
            ])
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
            ->when($status === 'overdue',  fn ($q) => $q->whereNull('return_date')
                ->whereDate('due_date', '<', today()))
            ->when($status === 'returned', fn ($q) => $q->whereNotNull('return_date'))
            ->when($from, fn ($q) => $q->whereDate('borrowing_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('borrowing_date', '<=', $to))
            ->groupBy(
                'member_id',
                'book_id',
                DB::raw('DATE(borrowing_date)'),
                DB::raw('DATE(due_date)'),
                DB::raw('(return_date IS NOT NULL)')
            )
            // newest borrowings first
            ->orderByRaw('DATE(borrowing_date) DESC')
            ->orderByRaw('MIN(borrowing_id) DESC')
            ->paginate(15)
            ->withQueryString();

        // These still count individual copies
        $stats = [
            'active'        => Borrowing::whereNull('return_date')->count(),
            'borrowedToday' => Borrowing::whereDate('borrowing_date', today())->count(),
            'overdue'       => Borrowing::whereNull('return_date')
                ->whereDate('due_date', '<', now())
                ->count(),
            'returnedToday' => Borrowing::whereDate('return_date', today())->count(),
            'available'     => Book::sum('available_quantity'),
        ];

        return view('borrowing.index', compact('borrowings', 'stats'));
    }

    public function create()
    {
        return view('borrowing.create', [
            // only active members can be picked
            'members'   => Member::active()->orderBy('full_name')->get(),
            'books'     => Book::with('category')->orderBy('title')->get(),
            'loanDays'  => self::LOAN_DAYS,
            'maxCopies' => self::MAX_COPIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // must exist AND be active (blocks crafted requests too)
            'member_id'      => ['required', Rule::exists('members', 'member_id')->where('status', 'active')],
            'book_id'        => 'required|exists:books,book_id',
            'quantity'       => 'required|integer|min:1|max:' . self::MAX_COPIES,
            'borrowing_date' => 'required|date|date_equals:today',
            'due_date'       => 'required|date|after_or_equal:borrowing_date',
        ], [
            'member_id.required'         => 'Please pick a member from the suggestions.',
            'member_id.exists'           => 'This member is not active and cannot borrow books.',
            'book_id.required'           => 'Please pick a book from the suggestions.',
            'quantity.max'               => 'You can borrow at most ' . self::MAX_COPIES . ' copies at a time.',
            'borrowing_date.date_equals' => 'The borrowing date must be today.',
        ]);

        // All copies are saved together, or none are
        $ids = DB::transaction(function () use ($data) {
            $book = Book::lockForUpdate()->findOrFail($data['book_id']);

            if ($book->available_quantity < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => $book->available_quantity < 1
                        ? 'No copies of this book are available.'
                        : "Only {$book->available_quantity} copies are available.",
                ]);
            }

            $userId = Auth::id() ?? User::query()->value('user_id');
            $ids = [];

            for ($i = 0; $i < $data['quantity']; $i++) {
                $ids[] = Borrowing::create([
                    'book_id'        => $book->book_id,
                    'member_id'      => $data['member_id'],
                    'user_id'        => $userId,
                    'borrowing_date' => $data['borrowing_date'],
                    'due_date'       => $data['due_date'],
                    'status'         => 'borrowed',
                ])->borrowing_id;
            }

            $book->decrement('available_quantity', $data['quantity']);

            return $ids;
        });

        return redirect()
            ->route('borrowing.show', $ids[0])
            ->with('created', true)
            ->with('created_ids', $ids);
    }

    public function show(Borrowing $borrowing)
    {
        $borrowing->load(['member', 'book.category']);

        // Every row this member has for this book, grouped into transactions
        // (same borrow date, due date and status), the same rule the member page uses.
        $transactions = Borrowing::where('member_id', $borrowing->member_id)
            ->where('book_id', $borrowing->book_id)
            ->orderBy('borrowing_date')
            ->orderBy('borrowing_id')
            ->get()
            ->groupBy(fn ($b) =>
                optional($b->borrowing_date)->toDateString() . '|' .
                optional($b->due_date)->toDateString() . '|' .
                $b->display_status
            )
            ->map(fn ($group) => (object) [
                'copies'         => $group->count(),
                'borrowing_id'   => $group->first()->borrowing_id,
                'ids'            => $group->pluck('borrowing_id')->all(),
                'display_status' => $group->first()->display_status,
                'borrowing_date' => $group->first()->borrowing_date,
                'due_date'       => $group->first()->due_date,
            ])
            ->values();

        // The transaction being viewed
        $current = $transactions->first(fn ($t) => in_array($borrowing->borrowing_id, $t->ids));

        // The other transactions this member has for the same book
        $others = $transactions->reject(fn ($t) => $t === $current)->values();

        return view('borrowing.show', compact('borrowing', 'current', 'others'));
    }

    public function export(Request $request)
    {
        $rows = Borrowing::with(['member', 'book'])
            ->filter($request->query())
            ->orderByDesc('borrowing_date')
            ->orderByDesc('borrowing_id')
            ->get();

        // stops Excel from running a cell that starts with = + - @ as a formula
        $safe = fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 correctly

            fputcsv($out, ['Transaction ID', 'Member Code', 'Member', 'Book',
                'Date Borrowed', 'Due Date', 'Return Date', 'Status']);

            foreach ($rows as $b) {
                fputcsv($out, [
                    'B-' . str_pad($b->borrowing_id, 3, '0', STR_PAD_LEFT),
                    $safe($b->member->member_code ?? ''),
                    $safe($b->member->full_name ?? ''),
                    $safe($b->book->title ?? ''),
                    $b->borrowing_date?->format('Y-m-d'),
                    $b->due_date?->format('Y-m-d'),
                    $b->return_date?->format('Y-m-d'),
                    $b->display_status,
                ]);
            }

            fclose($out);
        }, 'borrowings-' . today()->toDateString() . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}