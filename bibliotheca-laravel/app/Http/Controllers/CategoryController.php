<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount('books')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('category_name', 'like', '%' . $request->search . '%');
            })
            ->orderBy('category_id')
            ->paginate(10)
            ->withQueryString();

        $totalCategories = Category::count();

        return view('category.categories', compact('categories', 'totalCategories'));
    }

    public function show(Request $request, Category $category)
    {
        $search = trim((string) $request->query('search', ''));

        $books = $category->books()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'titles'    => $category->books()->count(),
            'available' => $category->books()->where('available_quantity', '>', 0)->count(),
        ];

        return view('category.show', compact('category', 'books', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => ['required', 'string', 'max:255', 'unique:categories,category_name'],
        ]);

        Category::create([
            'category_name' => $request->category_name,
        ]);

        return redirect()->route('categories')
            ->with('categories_success', 'Category added successfully.');
    }

    public function update(Request $request, Category $category)
    {
        // Errors go into the "editCategory" bag so only the edit modal shows them
        $request->validateWithBag('editCategory', [
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'category_name')->ignore($category->category_id, 'category_id'),
            ],
        ]);

        $category->update([
            'category_name' => $request->category_name,
        ]);

        return redirect()->route('categories')
            ->with('categories_success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        // Block deleting a category that still has books
        if ($category->books()->exists()) {
            return redirect()->route('categories')
                ->with('categories_error', 'Cannot delete this category because it still has books.');
        }

        $category->delete();

        return redirect()->route('categories')
            ->with('categories_success', 'Category deleted successfully.');
    }
}