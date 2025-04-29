<?php

namespace App\Http\Controllers;

use App\Models\BookCopy;
use App\Models\Book;
use Illuminate\Http\Request;

class BookCopyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the book copies.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $bookCopies = BookCopy::with('book')->get();
        return view('book_copies.index', compact('bookCopies'));
    }

    /**
     * Show the form for creating a new book copy.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $books = Book::all();
        return view('book_copies.create', compact('books'));
    }

    /**
     * Store a newly created book copy in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'status' => 'required|in:available,loaned,reserved,maintenance',
        ]);

        BookCopy::create([
            'book_id' => $request->book_id,
            'acquisition_date' => $request->acquisition_date ?? now(),
            'status' => $request->status,
        ]);

        return redirect()->route('book-copies.index')
            ->with('success', 'Book copy created successfully.');
    }

    /**
     * Display the specified book copy.
     *
     * @param  \App\Models\BookCopy  $bookCopy
     * @return \Illuminate\View\View
     */
    public function show(BookCopy $bookCopy)
    {
        return view('book_copies.show', compact('bookCopy'));
    }

    /**
     * Show the form for editing the specified book copy.
     *
     * @param  \App\Models\BookCopy  $bookCopy
     * @return \Illuminate\View\View
     */
    public function edit(BookCopy $bookCopy)
    {
        $books = Book::all();
        return view('book_copies.edit', compact('bookCopy', 'books'));
    }

    /**
     * Update the specified book copy in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BookCopy  $bookCopy
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, BookCopy $bookCopy)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'status' => 'required|in:available,loaned,reserved,maintenance',
        ]);

        $bookCopy->update([
            'book_id' => $request->book_id,
            'acquisition_date' => $request->acquisition_date,
            'status' => $request->status,
        ]);

        return redirect()->route('book-copies.index')
            ->with('success', 'Book copy updated successfully.');
    }

    /**
     * Remove the specified book copy from storage.
     *
     * @param  \App\Models\BookCopy  $bookCopy
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(BookCopy $bookCopy)
    {
        // Check if book copy has active loans before deletion
        if ($bookCopy->loans()->where('return_date', null)->count() > 0) {
            return redirect()->route('book-copies.index')
                ->with('error', 'Cannot delete book copy with active loans.');
        }

        $bookCopy->delete();

        return redirect()->route('book-copies.index')
            ->with('success', 'Book copy deleted successfully.');
    }
}