<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
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
     * Display the reports dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Generate and display overdue loans report.
     *
     * @return \Illuminate\View\View
     */
    public function overdueLoans()
    {
        $overdueLoans = Loan::whereNull('return_date')
            ->where('due_date', '<', now())
            ->with(['bookCopy.book', 'member'])
            ->get();

        // Create report record
        $report = Report::create([
            'report_name' => 'Overdue Loans',
            'report_type' => 'loans',
            'generated_date' => now(),
            'generated_by' => auth()->id(),
        ]);

        return view('reports.overdue_loans', compact('overdueLoans', 'report'));
    }

    /**
     * Generate and display popular books report.
     *
     * @return \Illuminate\View\View
     */
    public function popularBooks(Request $request)
    {
        $period = $request->period ?? 30; // Default 30 days

        $popularBooks = DB::table('loans')
            ->join('book_copies', 'loans.copy_id', '=', 'book_copies.id')
            ->join('books', 'book_copies.book_id', '=', 'books.id')
            ->select('books.id', 'books.book_title', DB::raw('count(*) as loan_count'))
            ->where('loans.loan_date', '>=', now()->subDays($period))
            ->groupBy('books.id', 'books.book_title')
            ->orderByDesc('loan_count')
            ->limit(10)
            ->get();

        // Create report record
        $report = Report::create([
            'report_name' => 'Popular Books - Last ' . $period . ' Days',
            'report_type' => 'books',
            'generated_date' => now(),
            'generated_by' => auth()->id(),
        ]);

        return view('reports.popular_books', compact('popularBooks', 'report', 'period'));
    }

    /**
     * Generate and display member activity report.
     *
     * @return \Illuminate\View\View
     */
    public function memberActivity(Request $request)
    {
        $period = $request->period ?? 30; // Default 30 days

        $memberActivity = DB::table('members')
            ->leftJoin('loans', 'members.id', '=', 'loans.member_id')
            ->select(
                'members.id',
                'members.first_name',
                'members.last_name',
                DB::raw('count(loans.id) as loan_count')
            )
            ->where(function($query) use ($period) {
                $query->where('loans.loan_date', '>=', now()->subDays($period))
                    ->orWhereNull('loans.loan_date');
            })
            ->groupBy('members.id', 'members.first_name', 'members.last_name')
            ->orderByDesc('loan_count')
            ->get();

        // Create report record
        $report = Report::create([
            'report_name' => 'Member Activity - Last ' . $period . ' Days',
            'report_type' => 'members',
            'generated_date' => now(),
            'generated_by' => auth()->id(),
        ]);

        return view('reports.member_activity', compact('memberActivity', 'report', 'period'));
    }

    /**
     * Generate and display financial summary report.
     *
     * @return \Illuminate\View\View
     */
    public function financialSummary(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now()->endOfMonth();

        $financialSummary = DB::table('transactions')
            ->join('fee_types', 'transactions.fee_type_id', '=', 'fee_types.id')
            ->select(
                'fee_types.name',
                DB::raw('SUM(transactions.amount) as total_amount'),
                DB::raw('COUNT(transactions.id) as transaction_count')
            )
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('fee_types.name')
            ->get();

        $totalAmount = $financialSummary->sum('total_amount');
        $totalTransactions = $financialSummary->sum('transaction_count');

        // Create report record
        $report = Report::create([
            'report_name' => 'Financial Summary',
            'report_type' => 'financial',
            'generated_date' => now(),
            'generated_by' => auth()->id(),
        ]);

        return view('reports.financial_summary', compact(
            'financialSummary',
            'report',
            'startDate',
            'endDate',
            'totalAmount',
            'totalTransactions'
        ));
    }

    /**
     * Generate and display inventory status report.
     *
     * @return \Illuminate\View\View
     */
    public function inventoryStatus()
    {
        $inventoryStatus = [
            'total_books' => Book::count(),
            'total_copies' => BookCopy::count(),
            'available_copies' => BookCopy::where('status', 'available')->count(),
            'loaned_copies' => BookCopy::where('status', 'loaned')->count(),
            'reserved_copies' => BookCopy::where('status', 'reserved')->count(),
            'maintenance_copies' => BookCopy::where('status', 'maintenance')->count(),
        ];

        $categoryDistribution = DB::table('books')
            ->join('categories', 'books.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('count(*) as book_count'))
            ->groupBy('categories.name')
            ->get();

        // Create report record
        $report = Report::create([
            'report_name' => 'Inventory Status',
            'report_type' => 'inventory',
            'generated_date' => now(),
            'generated_by' => auth()->id(),
        ]);

        return view('reports.inventory_status', compact(
            'inventoryStatus',
            'categoryDistribution',
            'report'
        ));
    }

    /**
     * Show the form for creating a custom report.
     *
     * @return \Illuminate\View\View
     */
    public function createCustomReport()
    {
        return view('reports.create_custom');
    }

    /**
     * Generate a custom report based on user input.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function generateCustomReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:books,loans,members,transactions',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $reportType = $request->report_type;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        
        $data = [];
        
        switch ($reportType) {
            case 'books':
                $data = Book::withCount(['loans' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('loan_date', [$startDate, $endDate]);
                }])->get();
                break;
            
            case 'loans':
                $data = Loan::with(['bookCopy.book', 'member'])
                    ->whereBetween('loan_date', [$startDate, $endDate])
                    ->get();
                break;
                
            case 'members':
                $data = Member::withCount(['loans' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('loan_date', [$startDate, $endDate]);
                }])->get();
                break;
                
            case 'transactions':
                $data = Transaction::with(['member', 'feeType'])
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->get();
                break;
        }
        
        // Create report record
        $report = Report::create([
            'report_name' => 'Custom ' . ucfirst($reportType) . ' Report',
            'report_type' => $reportType,
            'generated_date' => now(),
            'generated_by' => auth()->id(),
        ]);
        
        return view('reports.custom_report', compact('data', 'report', 'reportType', 'startDate', 'endDate'));
    }

    /**
     * Display a specific saved report.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\View\View
     */
    public function show(Report $report)
    {
        return view('reports.show', compact('report'));
    }

    /**
     * Remove the specified report from storage.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Report $report)
    {
        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Report deleted successfully.');
    }
}