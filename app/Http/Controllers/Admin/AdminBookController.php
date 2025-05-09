<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class AdminBookController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', \App\Http\Middleware\AdminMiddleware::class]);
    }

    public function index()
    {
        $books = \App\Models\Book::orderBy('created_at', 'desc')->get();
        $categories = \App\Models\Category::all();
        return view('admin.books.index', compact('books', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_title' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'publisher' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:categories,category_id',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string|max:2000',
        ]);

        $book = new Book($validated);
        $book->description = $request->description;

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('covers');
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $book->cover = 'covers/' . $filename;
        }

        $book->save();

        // Create a book copy for the new book
        \App\Models\BookCopy::create([
            'book_id' => $book->book_id, // use the correct PK field
            // 'acquisition_date' => now(), // optional, will default to null if not set
            // 'status' => 'available', // optional, will default to 'available' due to migration
        ]);

        return redirect()->route('admin.books.index')
            ->with('success', 'Book and its first copy added successfully!');
    }

    public function edit(Book $book)
    {
        return view('admin.books.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'book_title' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'publisher' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:categories,category_id',
        ]);

        $book->update($validated);

        return redirect()->route('admin.books.index')
            ->with('success', 'Book updated successfully!');
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return redirect()->route('admin.books.index')
            ->with('success', 'Book deleted successfully!');
    }
} 