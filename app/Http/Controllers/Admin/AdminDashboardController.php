<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['web', \App\Http\Middleware\AdminMiddleware::class]);
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Dashboard stats
        $totalBooks = \App\Models\Book::count();
        $booksBorrowed = \App\Models\Loan::whereNull('return_date')->count();
        $totalMembers = \App\Models\Member::count();
        $overdueBooksCount = \App\Models\Loan::whereNull('return_date')->where('due_date', '<', now())->count();

        // Recent Activity (last 10 actions: borrow, return, reserve, add book)
        $recentLoans = \App\Models\Loan::with(['bookCopy.book', 'member'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($loan) {
                $action = $loan->returned_date ? 'returned' : 'borrowed';
                $time = $loan->returned_date ? $loan->returned_date : $loan->created_at;
                return [
                    'type' => $action,
                    'member' => $loan->member ? ($loan->member->first_name . ' ' . $loan->member->last_name) : 'Unknown Member',
                    'book_title' => ($loan->bookCopy && $loan->bookCopy->book) ? $loan->bookCopy->book->book_title : 'Unknown Book',
                    'time_ago' => $time->diffForHumans(),
                ];
            });
        $recentReservations = \App\Models\Reservation::with(['book', 'member'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($reservation) {
                return [
                    'type' => 'reserved',
                    'member' => $reservation->member ? ($reservation->member->first_name . ' ' . $reservation->member->last_name) : 'Unknown Member',
                    'book_title' => $reservation->book ? $reservation->book->book_title : 'Unknown Book',
                    'time_ago' => $reservation->created_at->diffForHumans(),
                ];
            });
        $recentBooks = \App\Models\Book::orderBy('created_at', 'desc')
            ->take(2)
            ->get()
            ->map(function ($book) {
                return [
                    'type' => 'added',
                    'book_title' => $book->book_title,
                    'time_ago' => $book->created_at->diffForHumans(),
                ];
            });
        $recentActivity = collect([])
            ->merge($recentLoans)
            ->merge($recentReservations)
            ->merge($recentBooks)
            ->sortByDesc('time_ago')
            ->take(10)
            ->values();

        // Alerts & Notifications
        $overdueBooks = \App\Models\Loan::with(['bookCopy.book', 'member'])
            ->whereNull('return_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->get();
        $pendingReservationsCount = \App\Models\Reservation::where('status', 'pending')->count();
        $recentBookAdditions = \App\Models\Book::orderBy('created_at', 'desc')->take(1)->get();

        // Existing data for other dashboard sections
        $members = \App\Models\Member::orderBy('join_date', 'desc')->get();
        $booksOnLoan = $booksBorrowed;
        $pendingReservations = \App\Models\Reservation::with(['book', 'member'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.index', compact(
            'totalBooks',
            'booksBorrowed',
            'totalMembers',
            'overdueBooksCount',
            'recentActivity',
            'overdueBooks',
            'pendingReservationsCount',
            'recentBookAdditions',
            'members',
            'booksOnLoan',
            'pendingReservations'
        ));
    }
}   