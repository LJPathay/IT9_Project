<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Author;
use Illuminate\Http\Request;

class BookController extends Controller
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
     * Display a listing of the books.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $books = Book::with(['category', 'authors'])->get();
        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new book.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = Category::all();
        $authors = Author::all();
        return view('books.create', compact('categories', 'authors'));
    }

    /**
     * Store a newly created book in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_title' => 'required',
            'isbn' => 'required|unique:books',
            'category_id' => 'required|exists:categories,id',
            'author_ids' => 'required|array',
            'author_ids.*' => 'exists:authors,id',
        ]);

        $book = Book::create([
            'book_title' => $request->book_title,
            'isbn' => $request->isbn,
            'publication_date' => $request->publication_date,
            'publisher' => $request->publisher,
            'category_id' => $request->category_id,
        ]);

        $book->authors()->attach($request->author_ids);

        return redirect()->route('books.index')
            ->with('success', 'Book created successfully.');
    }

    /**
     * Display the specified book.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\View\View
     */
    public function show(Book $book)
    {
        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified book.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\View\View
     */
    public function edit(Book $book)
    {
        $categories = Category::all();
        $authors = Author::all();
        $selectedAuthors = $book->authors->pluck('id')->toArray();
        return view('books.edit', compact('book', 'categories', 'authors', 'selectedAuthors'));
    }

    /**
     * Update the specified book in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Book $book)
    {
        $request->validate([
            'book_title' => 'required',
            'isbn' => 'required|unique:books,isbn,' . $book->id,
            'category_id' => 'required|exists:categories,id',
            'author_ids' => 'required|array',
            'author_ids.*' => 'exists:authors,id',
        ]);

        $book->update([
            'book_title' => $request->book_title,
            'isbn' => $request->isbn,
            'publication_date' => $request->publication_date,
            'publisher' => $request->publisher,
            'category_id' => $request->category_id,
        ]);

        $book->authors()->sync($request->author_ids);

        return redirect()->route('books.index')
            ->with('success', 'Book updated successfully.');
    }

    /**
     * Remove the specified book from storage.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Book $book)
    {
        $book->authors()->detach();
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }
}