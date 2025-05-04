<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Book;
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
        $user = Auth::user();
        if (!$user->member) {
            return redirect()->route('dashboard')->with('error', 'No member profile found for your account.');
        }
        
        $memberId = $user->member->member_id;
        
        // Get active reservations
        $activeReservations = Reservation::with(['book'])
            ->where('member_id', $memberId)
            ->whereIn('status', ['pending', 'ready'])
            ->orderBy('reservation_date', 'desc')
            ->get();
            
        // Get reservation history
        $reservationHistory = Reservation::with(['book'])
            ->where('member_id', $memberId)
            ->whereIn('status', ['completed', 'cancelled', 'expired'])
            ->orderBy('reservation_date', 'desc')
            ->limit(10) // Limit to last 10 for performance
            ->get();
        
        return view('Reservations.index', [
            'activeReservations' => $activeReservations,
            'reservationHistory' => $reservationHistory
        ]);
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
        $request->validate([
            'book_id' => 'required|exists:books,book_id',
        ]);
        
        $user = Auth::user();
        if (!$user->member) {
            return redirect()->route('dashboard')->with('error', 'No member profile found for your account.');
        }
        $memberId = $user->member->member_id;
        
        // Check if user already has a reservation for this book
        $existingReservation = Reservation::where('member_id', $memberId)
            ->where('book_id', $request->book_id)
            ->whereIn('status', ['pending', 'ready'])
            ->first();
            
        if ($existingReservation) {
            return redirect()->back()->with('error', 'You already have a reservation for this book.');
        }
        
        // Check if any copies are available for reservation
        $book = Book::with('bookCopies')->find($request->book_id);
        $availableForReservation = $book->bookCopies->where('status', 'available')->count() > 0;
        
        // Create reservation
        $reservation = new Reservation();
        $reservation->member_id = $memberId;
        $reservation->book_id = $request->book_id;
        $reservation->reservation_date = Carbon::now();
        $reservation->expiry_date = Carbon::now()->addDays(7); // Reservations valid for 7 days
        $reservation->status = $availableForReservation ? 'ready' : 'pending';
        $reservation->save();
        
        $message = $availableForReservation 
            ? 'Book reserved successfully! It is ready for pickup.' 
            : 'Book reserved successfully! You will be notified when it becomes available.';
            
        return redirect()->route('reservations.index')->with('success', $message);
    }

    /**
     * Cancel a reservation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $user = Auth::user();
        if (!$user->member) {
            return redirect()->route('dashboard')->with('error', 'No member profile found for your account.');
        }
        $memberId = $user->member->member_id;
        
        $reservation = Reservation::where('reservation_id', $id)
            ->where('member_id', $memberId)
            ->whereIn('status', ['pending', 'ready'])
            ->first();
            
        if (!$reservation) {
            return redirect()->route('reservations.index')->with('error', 'Reservation not found or cannot be cancelled.');
        }
        
        $reservation->status = 'cancelled';
        $reservation->completion_date = Carbon::now();
        $reservation->save();
        
        return redirect()->route('reservations.index')->with('success', 'Reservation cancelled successfully.');
    }

    /**
     * Convert a reservation to a loan.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function checkout($id)
    {
        $user = Auth::user();
        if (!$user->member) {
            return redirect()->route('dashboard')->with('error', 'No member profile found for your account.');
        }
        $memberId = $user->member->member_id;
        
        $reservation = Reservation::where('reservation_id', $id)
            ->where('member_id', $memberId)
            ->where('status', 'ready')
            ->first();
            
        if (!$reservation) {
            return redirect()->route('reservations.index')->with('error', 'Reservation not found or not ready for checkout.');
        }
        
        // Find an available copy of the book
        $book = Book::with(['bookCopies' => function($query) {
            $query->where('status', 'available');
        }])->find($reservation->book_id);
        
        if ($book->bookCopies->isEmpty()) {
            return redirect()->route('reservations.index')->with('error', 'No copies available for checkout.');
        }
        
        $bookCopy = $book->bookCopies->first();
        
        // Create a new loan
        $loan = new \App\Models\Loan();
        $loan->member_id = $memberId;
        $loan->copy_id = $bookCopy->copy_id;
        $loan->borrowed_date = Carbon::now();
        $loan->due_date = Carbon::now()->addDays(14); // Standard loan period of 14 days
        $loan->status = 'borrowed';
        $loan->save();
        
        // Update book copy status
        $bookCopy->status = 'borrowed';
        $bookCopy->save();
        
        // Mark reservation as completed
        $reservation->status = 'completed';
        $reservation->completion_date = Carbon::now();
        $reservation->save();
        
        return redirect()->route('loans.index')->with('success', 'Book checked out successfully.');
    }
}