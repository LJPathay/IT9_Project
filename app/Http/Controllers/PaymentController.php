<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $payments = Payment::with('transaction.user')->get();
        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        $transactions = Transaction::where('status', 'pending')
            ->with('user')
            ->get();
        return view('payments.create', compact('transactions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,transaction_id',
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required',
        ]);

        $transaction = Transaction::where('transaction_id', $request->transaction_id)->firstOrFail();

        Payment::create([
            'transaction_id' => $request->transaction_id,
            'payment_amount' => $request->payment_amount,
            'payment_date' => $request->payment_date ?? now(),
            'payment_method' => $request->payment_method,
        ]);

        $totalPaid = $transaction->payments()->sum('payment_amount');
        if ($totalPaid >= $transaction->amount) {
            $transaction->update(['status' => 'paid']);
        }

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $transactions = Transaction::with('user')->get();
        return view('payments.edit', compact('payment', 'transactions'));
    }

    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required',
        ]);

        $payment->update([
            'payment_amount' => $request->payment_amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
        ]);

        $transaction = $payment->transaction;
        $totalPaid = $transaction->payments()->sum('payment_amount');
        $transaction->update([
            'status' => $totalPaid >= $transaction->amount ? 'paid' : 'pending'
        ]);

        return redirect()->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $transaction = $payment->transaction;

        $payment->delete();

        $totalPaid = $transaction->payments()->sum('payment_amount');
        $transaction->update([
            'status' => $totalPaid >= $transaction->amount ? 'paid' : 'pending'
        ]);

        return redirect()->route('payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
}
