<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\FeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeesPaymentController extends Controller
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
     * Display the fees and payments page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get all transactions for the current user
        $transactions = Transaction::where('member_id', $user->id)
            ->with('feeType')
            ->get();
            
        // Get pending transactions
        $pendingTransactions = $transactions->where('status', 'pending');
        
        // Calculate total, pending, and paid fees
        $totalFees = $transactions->sum('amount');
        $pendingFees = $pendingTransactions->sum('amount');
        $paidFees = $totalFees - $pendingFees;
        
        // Get all payments for the current user
        $payments = Payment::whereHas('transaction', function ($query) use ($user) {
                $query->where('member_id', $user->id);
            })
            ->with('transaction')
            ->orderBy('payment_date', 'desc')
            ->get();

        // Changed from 'fees-payment.index' to 'fees_payment.index'
        return view('fees_payments.index', compact(
            'payments',
            'transactions',
            'pendingTransactions',
            'totalFees',
            'pendingFees',
            'paidFees'
        ));
    }
}