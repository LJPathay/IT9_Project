<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Book;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    /**
     * Display a listing of the user's reservations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $member = Member::where('email', Auth::user()->email)->first();
        
        if (!$member) {
            return redirect()->route('profile.index')
                ->with('error', 'Please complete your member profile first.');
        }

        $activeReservations = Reservation::with(['book', 'member'])
            ->where('member_id', $member->member_id)
            ->whereIn('status', ['pending', 'ready'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $reservationHistory = Reservation::with(['book', 'member'])
            ->where('member_id', $member->member_id)
            ->whereIn('status', ['completed', 'cancelled', 'expired'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $pendingReservations = Reservation::where('status', 'pending')->get();

        return view('reservations.index', compact('activeReservations', 'reservationHistory', 'pendingReservations'));
    }

    /**
     * Show the form for creating a new reservation.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // This could be used if you have a separate reservation form
        // But typically reservations are created from the book details page
        return redirect()->route('books.index');
    }

    /**
     * Store a newly created reservation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            \Log::info('Reservation request received', [
                'request_data' => $request->all(),
                'user_email' => Auth::user()->email
            ]);

            $request->validate([
                'book_id' => 'required|exists:books,book_id',
                'reservation_date' => 'required|date|after:today'
            ]);

            $member = Member::where('email', Auth::user()->email)->first();
            
            \Log::info('Member lookup result', [
                'member_found' => $member ? true : false,
                'member_id' => $member ? $member->member_id : null
            ]);
            
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete your member profile first.'
                ], 403);
            }

            // Check if book is already reserved by this member
            $existingReservation = Reservation::where('book_id', $request->book_id)
                ->where('member_id', $member->member_id)
                ->whereIn('status', ['pending', 'ready'])
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reserved this book.'
                ], 422);
            }

            $reservation = Reservation::create([
                'book_id' => $request->book_id,
                'member_id' => $member->member_id,
                'reservation_date' => $request->reservation_date,
                'status' => 'pending'
            ]);

            \Log::info('Reservation created successfully', [
                'reservation_id' => $reservation->reservation_id
            ]);

            // If AJAX, return JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book reserved successfully!',
                    'reservation' => $reservation
                ]);
            }

            // Otherwise, redirect back with a session flash message
            return redirect()->back()->with('success', 'Reservation booked successfully!');
        } catch (\Exception $e) {
            \Log::error('Error creating reservation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a reservation.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function cancel(Reservation $reservation)
    {
        $member = Member::where('email', Auth::user()->email)->first();
        
        if (!$member || $reservation->member_id !== $member->member_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending reservations can be cancelled.'
            ], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully.'
        ]);
    }

    /**
     * Convert a reservation to a loan.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function checkout($id)
    {
        $member = Member::where('email', Auth::user()->email)->first();
        
        if (!$member) {
            return redirect()->route('dashboard')->with('error', 'No member profile found for your account.');
        }
        
        $reservation = Reservation::where('reservation_id', $id)
            ->where('member_id', $member->member_id)
            ->where('status', 'ready')
            ->first();
            
        if (!$reservation) {
            return redirect()->route('reservations.index')->with('error', 'Reservation not found or not ready for checkout.');
        }
        
        // Find an available copy of the book
        $book = Book::with(['bookCopies' => function($query) {
            $query->where('status', 'available');
        }])->find($reservation->book_id);
        
        $onLoanCopies = $book->bookCopies->whereIn('status', ['loaned', 'borrowed'])->count();
        $isAvailable = $onLoanCopies > 0;
        
        if (!$isAvailable) {
            return redirect()->route('reservations.index')->with('error', 'No copies available for checkout.');
        }
        
        $bookCopy = $book->bookCopies->first();
        
        // Create a new loan
        $loan = new \App\Models\Loan();
        $loan->member_id = $member->member_id;
        $loan->copy_id = $bookCopy->copy_id;
        $loan->borrowed_date = Carbon::now();
        $loan->due_date = Carbon::now()->addDays(4); // Standard loan period of 14 days
        $loan->status = 'borrowed';
        $loan->save();
        
        // Update book copy status
        $bookCopy->status = 'loaned';
        $bookCopy->save();
        
        // Mark reservation as completed
        $reservation->status = 'completed';
        $reservation->completion_date = Carbon::now();
        $reservation->save();
        
        return redirect()->route('loans.index')->with('success', 'Book checked out successfully.');
    }

    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->status = $request->status;
        $reservation->save();
        
        return redirect()->back()->with('success', 'Reservation status updated successfully');
    }
}