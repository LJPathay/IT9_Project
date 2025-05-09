<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Book::with('category');

        // Filter by category if provided
        if ($request->has('category') && $request->category !== '') {
            $query->where('category_id', $request->category);
        }

        $books = $query->get();

        // If it's an AJAX request, return JSON
        if ($request->ajax()) {
            return response()->json([
                'books' => $books
            ]);
        }

        return view('books.index', compact('books'));
    }

    /**
     * Display the specified book.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $book = Book::with('category')->where('book_id', $id)->firstOrFail();
        return view('books.show', compact('book'));
    }

    public function borrow(Request $request, $id)
    {
        $book = Book::with('bookCopies')->where('book_id', $id)->firstOrFail();
        $user = auth()->user();
        $member = \App\Models\Member::where('email', $user->email)->first();
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'No member profile found.'], 403);
        }
        $copy = $book->bookCopies()->where('status', 'available')->first();
        if (!$copy) {
            return response()->json(['success' => false, 'message' => 'No available copies.'], 422);
        }
        // Mark as loaned
        $copy->status = 'loaned';
        $copy->save();
        // Debug: check copy_id
        if (!$copy->copy_id) {
            return response()->json(['success' => false, 'message' => 'Copy ID not found.'], 500);
        }
        // Create a loan record
        $loan = \App\Models\Loan::create([
            'member_id' => $member->member_id,
            'copy_id' => $copy->copy_id,
            'loan_date' => now(),
            'due_date' => now()->addDays(14),
        ]);
        return response()->json(['success' => true, 'message' => 'Book borrowed successfully!']);
    }
}