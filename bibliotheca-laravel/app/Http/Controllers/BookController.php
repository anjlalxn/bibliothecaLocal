<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    // Allowed sort options: key => [column, direction]
    private const SORTS = [
        'id'         => ['book_id', 'asc'],
        'title_asc'  => ['title', 'asc'],
        'title_desc' => ['title', 'desc'],
        'author_asc' => ['author', 'asc'],
        'year_desc'  => ['publication_year', 'desc'],
        'year_asc'   => ['publication_year', 'asc'],
    ];

    // Display all books
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        // ------------------------------------------
        // Read and sanitize filters
        // ------------------------------------------
        $maxYear = (int) date('Y');

        $categoryId = $this->intOrNull($request->query('category_id'), 1);

        $status = in_array($request->query('status'), ['available', 'unavailable'], true)
            ? $request->query('status')
            : null;

        $yearFrom = $this->intOrNull($request->query('year_from'), 1000, $maxYear);
        $yearTo   = $this->intOrNull($request->query('year_to'), 1000, $maxYear);

        // If the range is reversed, swap it instead of returning nothing
        if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
            [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
        }

        $sort = array_key_exists((string) $request->query('sort'), self::SORTS)
            ? $request->query('sort')
            : 'id';

        [$sortColumn, $sortDirection] = self::SORTS[$sort];

        // ------------------------------------------
        // Build the query
        // ------------------------------------------
        $query = Book::with('category')
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
            ->when($categoryId !== null, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($status === 'available', function ($query) {
                // Same rule the table badge uses
                $query->whereRaw('LOWER(status) = ?', ['available'])
                      ->where('available_quantity', '>', 0);
            })
            ->when($status === 'unavailable', function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('status')
                      ->orWhereRaw('LOWER(status) != ?', ['available'])
                      ->orWhere('available_quantity', '<=', 0);
                });
            })
            ->when($yearFrom !== null, function ($query) use ($yearFrom) {
                $query->where('publication_year', '>=', $yearFrom);
            })
            ->when($yearTo !== null, function ($query) use ($yearTo) {
                $query->where('publication_year', '<=', $yearTo);
            });

        // Books with no publication year always go last when sorting by year
        if (str_starts_with($sort, 'year_')) {
            $query->orderByRaw('publication_year IS NULL');
        }

        $books = $query
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('book_id')
            ->paginate(15)
            ->withQueryString();

        // Count how many filters are active (for the badge)
        $activeFilterCount = collect([
            $categoryId,
            $status,
            $yearFrom,
            $yearTo,
            $sort !== 'id' ? $sort : null,
        ])->filter(fn ($value) => $value !== null)->count();

        return view('books.index', [
            'books' => $books,
            'categories' => Category::orderBy('category_name')->get(),
            'totalBooks' => Book::sum('quantity'),
            'totalTitles' => Book::count(),
            'filters' => [
                'category_id' => $categoryId,
                'status'      => $status,
                'year_from'   => $yearFrom,
                'year_to'     => $yearTo,
                'sort'        => $sort,
            ],
            'activeFilterCount' => $activeFilterCount,
        ]);
    }

    // Add a new book
    public function store(Request $request)
    {
        $this->normalizeInput($request);

        $data = $request->validate($this->rules());

        $data = $this->cleanOptionalFields($data);

        if ($this->isDuplicate($data)) {
            return back()
                ->withErrors([
                    'title' => 'This book already exists in your catalog (same title and author). Update its quantity instead of adding another record.'
                ])
                ->withInput();
        }

        // Set available quantity based on book status
        $data['available_quantity'] =
            $data['status'] === 'available'
                ? $data['quantity']
                : 0;

        $book = Book::create($data);

        return redirect()
            ->route('books')
            ->with('books_success', "Book \"{$book->title}\" added to the catalog.");
    }

    // Update an existing book
    public function update(Request $request, Book $book)
    {
        $this->normalizeInput($request);

        // Errors go into the "editBook" bag so only the edit modal reopens
        $data = $request->validateWithBag('editBook', $this->rules());

        $data = $this->cleanOptionalFields($data);

        // Duplicate check, ignoring the book being edited
        if ($this->isDuplicate($data, $book->book_id)) {
            return back()
                ->withErrors([
                    'title' => 'Another book with the same title and author already exists.'
                ], 'editBook')
                ->withInput();
        }

        // Copies currently borrowed out.
        // Only meaningful while the book is "available", because an
        // "unavailable" book has its available_quantity forced to 0.
        $borrowed = strtolower((string) $book->status) === 'available'
            ? max(0, $book->quantity - $book->available_quantity)
            : 0;

        if ($data['quantity'] < $borrowed) {
            return back()
                ->withErrors([
                    'quantity' => "Quantity cannot be lower than the {$borrowed} cop"
                        . ($borrowed === 1 ? 'y' : 'ies')
                        . ' currently borrowed.'
                ], 'editBook')
                ->withInput();
        }

        // Recompute available quantity
        $data['available_quantity'] =
            $data['status'] === 'available'
                ? $data['quantity'] - $borrowed
                : 0;

        $book->update($data);

        return redirect()
            ->route('books')
            ->with('books_success', "Book \"{$book->title}\" updated successfully.");
    }

    // Delete a book
    public function destroy(Book $book)
    {
        if ($book->borrowings()->exists()) {
            return back()->with(
                'books_error',
                'This book has borrowing records and cannot be deleted.'
            );
        }

        $title = $book->title;

        $book->delete();

        return back()->with(
            'books_success',
            "Book \"{$title}\" deleted successfully."
        );
    }

    // ==========================================
    // Shared helpers
    // ==========================================

    // Return an int within the range, or null if empty/invalid
    private function intOrNull($value, int $min, ?int $max = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $options = ['min_range' => $min];

        if ($max !== null) {
            $options['max_range'] = $max;
        }

        $result = filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]);

        return $result === false ? null : $result;
    }

    // Clean up unnecessary whitespace
    private function normalizeInput(Request $request): void
    {
        $request->merge([
            'title' => preg_replace(
                '/\s+/',
                ' ',
                trim((string) $request->input('title', ''))
            ),
            'author' => preg_replace(
                '/\s+/',
                ' ',
                trim((string) $request->input('author', ''))
            ),
            'publisher' => trim(
                (string) $request->input('publisher', '')
            ),
        ]);
    }

    // Validation rules shared by store and update
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,category_id',
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'status' => ['required', Rule::in(['available', 'unavailable'])],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => [
                'nullable',
                'integer',
                'min:1000',
                'max:' . date('Y'),
            ],
        ];
    }

    // Convert empty optional fields into NULL
    private function cleanOptionalFields(array $data): array
    {
        $data['publisher'] = $data['publisher'] ?: null;
        $data['publication_year'] = $data['publication_year'] ?: null;

        return $data;
    }

    // A book is a duplicate when another record has the same title AND
    // author (ignoring upper/lower case and extra spaces). Publisher and
    // year are NOT compared, so a blank or different publisher/year can no
    // longer sneak a duplicate through.
    // Pass $ignoreId to exclude the book currently being edited.
    private function isDuplicate(array $data, ?int $ignoreId = null): bool
    {
        $clean = fn (string $text) => mb_strtolower(
            trim(preg_replace('/\s+/', ' ', $text))
        );

        return Book::query()
            ->when($ignoreId, fn ($q) => $q->where('book_id', '!=', $ignoreId))
            ->whereRaw('LOWER(TRIM(title)) = ?', [$clean($data['title'])])
            ->whereRaw('LOWER(TRIM(author)) = ?', [$clean($data['author'])])
            ->exists();
    }
}