<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminFeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', \App\Http\Middleware\AdminMiddleware::class]);
    }

    public function index(Request $request)
    {
        // Get fee statistics
        $totalFees = Transaction::where('status', 'paid')->sum('amount');
        $pendingFees = Transaction::where('status', 'pending')->sum('amount');
        $overdueFees = Transaction::where('status', 'overdue')->sum('amount');
        $totalTransactions = Transaction::count();

        // Get fee types
        $feeTypes = FeeType::all();

        // Get transactions with filters
        $query = Transaction::with(['member', 'fee_type', 'loan.bookCopy.book']);

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Apply sorting
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'date_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'date_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'amount_desc':
                    $query->orderBy('amount', 'desc');
                    break;
                case 'amount_asc':
                    $query->orderBy('amount', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $transactions = $query->paginate(10);

        return view('admin.fees.index', compact(
            'totalFees',
            'pendingFees',
            'overdueFees',
            'totalTransactions',
            'feeTypes',
            'transactions'
        ));
    }

    public function updateFeeType(Request $request, $id)
    {
        $request->validate([
            'rate' => 'required|numeric|min:0',
            'description' => 'required|string|max:255'
        ]);

        $feeType = FeeType::findOrFail($id);
        $feeType->update($request->only(['rate', 'description']));

        return redirect()->back()->with('success', 'Fee type updated successfully.');
    }

    public function recordPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date'
        ]);

        $transaction = Transaction::findOrFail($id);
        
        if ($transaction->status !== 'pending') {
            return redirect()->back()->with('error', 'This transaction is not pending payment.');
        }

        $transaction->update([
            'status' => 'paid',
            'payment_date' => $request->payment_date,
            'amount' => $request->amount
        ]);

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function viewTransaction($id)
    {
        $transaction = Transaction::with(['member', 'fee_type'])
            ->findOrFail($id);

        return view('admin.fees.transaction-details', compact('transaction'));
    }
} 