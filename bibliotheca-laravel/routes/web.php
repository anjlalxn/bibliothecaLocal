<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Middleware\NoCache;
use Illuminate\Support\Facades\Route;

// Guests are bounced to /login by the auth middleware below.
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public: the only pages a guest can see
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

// Everything below requires a signed-in user,
// and is never cached by the browser (so Back can't reveal it after logout).
Route::middleware(['auth', NoCache::class])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Books
    Route::get('/books', [BookController::class, 'index'])->name('books');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // Borrowing
    Route::get('/borrowing/export', [BorrowingController::class, 'export'])->name('borrowing.export');
    Route::get('/borrowing', [BorrowingController::class, 'index'])->name('borrowing');
    Route::get('/borrowing/create', [BorrowingController::class, 'create'])->name('borrowing.create');
    Route::post('/borrowing', [BorrowingController::class, 'store'])->name('borrowing.store');
    Route::get('/borrowing/{borrowing}', [BorrowingController::class, 'show'])->name('borrowing.show');

    // Placeholder pages so every nav link works.

    Route::get('/return', [ReturnController::class, 'index'])->name('return');
    Route::post('/return/{borrowing}', [ReturnController::class, 'store'])->name('return.store');

    Route::get('/members', [MemberController::class, 'index'])->name('members');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/{member:member_id}', [MemberController::class, 'show'])->name('members.show');
    Route::put('/members/{member:member_id}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{member:member_id}', [MemberController::class, 'destroy'])->name('members.destroy');

    // Categories
    // {category:category_id} = bind to $category in the controller, looking up by the category_id column
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category:category_id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category:category_id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

});