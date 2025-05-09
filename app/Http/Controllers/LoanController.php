<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\BookCopy;
use App\Models\Member;
use Illuminate\Http\Request;

class LoanController extends Controller
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
     * Display a listing of the loans.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user();
        $activeLoans = Loan::with(['bookCopy.book', 'member'])
            ->where('member_id', $user->id)
            ->whereNull('return_date')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($loan) {
                $dueDate = \Carbon\Carbon::parse($loan->due_date);
                $now = \Carbon\Carbon::now();
                $daysUntilDue = $now->diffInDays($dueDate, false);
                
                return [
                    'id' => $loan->loan_id,
                    'book_id' => $loan->bookCopy->book->id,
                    'title' => $loan->bookCopy->book->title,
                    'publisher' => $loan->bookCopy->book->publisher,
                    'author' => $loan->bookCopy->book->author,
                    'borrowed_date' => \Carbon\Carbon::parse($loan->loan_date)->format('M d, Y'),
                    'due_date' => $dueDate->format('M d, Y'),
                    'status' => $daysUntilDue < 0 ? 'overdue' : ($daysUntilDue <= 3 ? 'duesoon' : 'ontime'),
                    'can_renew' => $loan->renewals < 2, // Assuming max 2 renewals
                    'renewals' => $loan->renewals ?? 0
                ];
            });

        $loanHistory = Loan::with(['bookCopy.book', 'member'])
            ->where('member_id', $user->id)
            ->whereNotNull('return_date')
            ->orderBy('return_date', 'desc')
            ->get()
            ->map(function ($loan) {
                $returnDate = \Carbon\Carbon::parse($loan->return_date);
                $dueDate = \Carbon\Carbon::parse($loan->due_date);
                
                return [
                    'id' => $loan->loan_id,
                    'book_id' => $loan->bookCopy->book->id,
                    'title' => $loan->bookCopy->book->title,
                    'publisher' => $loan->bookCopy->book->publisher,
                    'author' => $loan->bookCopy->book->author,
                    'borrowed_date' => \Carbon\Carbon::parse($loan->loan_date)->format('M d, Y'),
                    'returned_date' => $returnDate->format('M d, Y'),
                    'status' => $returnDate > $dueDate ? 'returned-late' : 'ontime'
                ];
            });

        return view('loans.index', compact('activeLoans', 'loanHistory'));
    }

    /**
     * Show the form for creating a new loan.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $bookCopies = BookCopy::where('status', 'available')->with('book')->get();
        $members = Member::all();
        return view('loans.create', compact('bookCopies', 'members'));
    }

    /**
     * Store a newly created loan in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'copy_id' => 'required|exists:book_copies,id',
            'member_id' => 'required|exists:members,id',
            'due_date' => 'required|date|after:today',
        ]);

        $bookCopy = BookCopy::findOrFail($request->copy_id);
        
        // Check if book copy is available
        if ($bookCopy->status !== 'available') {
            return redirect()->back()
                ->with('error', 'Book copy is not available for loan.')
                ->withInput();
        }

        // Create loan
        $loan = Loan::create([
            'copy_id' => $request->copy_id,
            'member_id' => $request->member_id,
            'loan_date' => $request->loan_date ?? now(),
            'due_date' => $request->due_date,
        ]);

        // Update book copy status
        $bookCopy->update(['status' => 'loaned']);

        return redirect()->route('loans.index')
            ->with('success', 'Loan created successfully.');
    }

    /**
     * Display the specified loan.
     *
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\View\View
     */
    public function show(Loan $loan)
    {
        return view('loans.show', compact('loan'));
    }

    /**
     * Show the form for editing the specified loan.
     *
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\View\View
     */
    public function edit(Loan $loan)
    {
        $bookCopies = BookCopy::with('book')->get();
        $members = Member::all();
        return view('loans.edit', compact('loan', 'bookCopies', 'members'));
    }

    /**
     * Update the specified loan in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Loan $loan)
    {
        $request->validate([
            'due_date' => 'required|date',
        ]);

        $loan->update([
            'due_date' => $request->due_date,
        ]);

        return redirect()->route('loans.index')
            ->with('success', 'Loan updated successfully.');
    }

    /**
     * Return the book for the specified loan.
     *
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function returnBook(Loan $loan)
    {
        // Check if book is already returned
        if ($loan->return_date) {
            return redirect()->route('loans.index')
                ->with('error', 'Book has already been returned.');
        }

        // Update loan with return date
        $loan->update([
            'return_date' => now(),
        ]);

        // Update book copy status
        $loan->bookCopy->update(['status' => 'available']);

        return redirect()->route('loans.index')
            ->with('success', 'Book returned successfully.');
    }

    /**
     * Remove the specified loan from storage.
     *
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Loan $loan)
    {
        // Check if loan has transactions before deletion
        if ($loan->transactions()->count() > 0) {
            return redirect()->route('loans.index')
                ->with('error', 'Cannot delete loan with associated transactions.');
        }

        // If book is still on loan, update status to available
        if (!$loan->return_date) {
            $loan->bookCopy->update(['status' => 'available']);
        }

        $loan->delete();

        return redirect()->route('loans.index')
            ->with('success', 'Loan deleted successfully.');
    }

    /**
     * Update the specified loan in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Loan  $loan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function returnLoan($id)
    {
        // Eager load the bookCopy relationship
        $loan = Loan::with('bookCopy')->find($id);

        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found'], 404);
        }

        // Update loan with return date and status
        $loan->update([
            'return_date' => now(),
            'status' => 'returned'
        ]);

        // Update the book status to 'available', with error handling
        $bookCopy = $loan->bookCopy;
        if ($bookCopy) {
            $bookCopy->update(['status' => 'available']);
        } else {
            return response()->json(['success' => false, 'message' => 'Book copy not found for this loan.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Book returned successfully']);
    }
}