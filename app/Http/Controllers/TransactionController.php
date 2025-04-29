<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Member;
use App\Models\Loan;
use App\Models\FeeType;
use Illuminate\Http\Request;

class TransactionController extends Controller
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
     * Display a listing of the transactions.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $transactions = Transaction::with(['member', 'loan', 'feeType'])->get();
        return view('transactions.index', compact('transactions'));
    }

    /**
     * Show the form for creating a new transaction.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $members = Member::all();
        $loans = Loan::whereNull('return_date')->with(['bookCopy.book', 'member'])->get();
        $feeTypes = FeeType::all();
        return view('transactions.create', compact('members', 'loans', 'feeTypes'));
    }

    /**
     * Store a newly created transaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
        ]);

        Transaction::create([
            'member_id' => $request->member_id,
            'loan_id' => $request->loan_id,
            'fee_type_id' => $request->fee_type_id,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date ?? now(),
            'status' => $request->status ?? 'pending',
        ]);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified transaction.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\View\View
     */
    public function show(Transaction $transaction)
    {
        return view('transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified transaction.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\View\View
     */
    public function edit(Transaction $transaction)
    {
        $members = Member::all();
        $loans = Loan::with(['bookCopy.book', 'member'])->get();
        $feeTypes = FeeType::all();
        return view('transactions.edit', compact('transaction', 'members', 'loans', 'feeTypes'));
    }

    /**
     * Update the specified transaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        $transaction->update([
            'fee_type_id' => $request->fee_type_id,
            'amount' => $request->amount,
            'status' => $request->status,
        ]);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Mark the transaction as paid.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsPaid(Transaction $transaction)
    {
        if ($transaction->status === 'paid') {
            return redirect()->route('transactions.index')
                ->with('error', 'Transaction is already paid.');
        }

        $transaction->update([
            'status' => 'paid',
        ]);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction marked as paid successfully.');
    }

    /**
     * Remove the specified transaction from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Transaction $transaction)
    {
        // Check if transaction has payments before deletion
        if ($transaction->payments()->count() > 0) {
            return redirect()->route('transactions.index')
                ->with('error', 'Cannot delete transaction with associated payments.');
        }

        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}