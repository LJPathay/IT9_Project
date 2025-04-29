<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends Controller
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
     * Display a listing of the payments.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $payments = Payment::with('transaction.member')->get();
        return view('payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new payment.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $transactions = Transaction::where('status', 'pending')
            ->with('member')
            ->get();
        return view('payments.create', compact('transactions'));
    }

    /**
     * Store a newly created payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);
        
        // Create payment
        Payment::create([
            'transaction_id' => $request->transaction_id,
            'payment_amount' => $request->payment_amount,
            'payment_date' => $request->payment_date ?? now(),
            'payment_method' => $request->payment_method,
        ]);

        // Check if payment covers the full amount
        $totalPaid = $transaction->payments()->sum('payment_amount');
        if ($totalPaid >= $transaction->amount) {
            $transaction->update(['status' => 'paid']);
        }

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Display the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\View\View
     */
    public function show(Payment $payment)
    {
        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\View\View
     */
    public function edit(Payment $payment)
    {
        $transactions = Transaction::with('member')->get();
        return view('payments.edit', compact('payment', 'transactions'));
    }

    /**
     * Update the specified payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required',
        ]);

        $oldTransaction = $payment->transaction;
        
        $payment->update([
            'payment_amount' => $request->payment_amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
        ]);

        // Update transaction status
        $transaction = $payment->transaction;
        $totalPaid = $transaction->payments()->sum('payment_amount');
        if ($totalPaid >= $transaction->amount) {
            $transaction->update(['status' => 'paid']);
        } else {
            $transaction->update(['status' => 'pending']);
        }

        return redirect()->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified payment from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Payment $payment)
    {
        $transaction = $payment->transaction;
        
        $payment->delete();
        
        // Update transaction status
        $totalPaid = $transaction->payments()->sum('payment_amount');
        if ($totalPaid >= $transaction->amount) {
            $transaction->update(['status' => 'paid']);
        } else {
            $transaction->update(['status' => 'pending']);
        }

        return redirect()->route('payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
}