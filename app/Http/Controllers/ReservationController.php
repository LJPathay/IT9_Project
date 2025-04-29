<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Book;
use App\Models\Member;
use Illuminate\Http\Request;

class ReservationController extends Controller
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
     * Display a listing of the reservations.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $reservations = Reservation::with(['book', 'member'])->get();
        return view('reservations.index', compact('reservations'));
    }

    /**
     * Show the form for creating a new reservation.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $books = Book::all();
        $members = Member::all();
        return view('reservations.create', compact('books', 'members'));
    }

    /**
     * Store a newly created reservation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'member_id' => 'required|exists:members,id',
        ]);

        // Check if there's already an active reservation for this book by this member
        $existingReservation = Reservation::where('book_id', $request->book_id)
            ->where('member_id', $request->member_id)
            ->where('status', 'active')
            ->first();

        if ($existingReservation) {
            return redirect()->back()
                ->with('error', 'Member already has an active reservation for this book.')
                ->withInput();
        }

        Reservation::create([
            'book_id' => $request->book_id,
            'member_id' => $request->member_id,
            'reservation_date' => $request->reservation_date ?? now(),
            'status' => 'active',
        ]);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation created successfully.');
    }

    /**
     * Display the specified reservation.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\View\View
     */
    public function show(Reservation $reservation)
    {
        return view('reservations.show', compact('reservation'));
    }

    /**
     * Show the form for editing the specified reservation.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\View\View
     */
    public function edit(Reservation $reservation)
    {
        $books = Book::all();
        $members = Member::all();
        return view('reservations.edit', compact('reservation', 'books', 'members'));
    }

    /**
     * Update the specified reservation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Reservation $reservation)
    {
        $request->validate([
            'status' => 'required|in:active,fulfilled,cancelled',
        ]);

        $reservation->update([
            'status' => $request->status,
        ]);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation updated successfully.');
    }

    /**
     * Mark the reservation as fulfilled.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function fulfill(Reservation $reservation)
    {
        // Check if reservation is already fulfilled or cancelled
        if ($reservation->status !== 'active') {
            return redirect()->route('reservations.index')
                ->with('error', 'Reservation is not active.');
        }

        $reservation->update([
            'status' => 'fulfilled',
        ]);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation fulfilled successfully.');
    }

    /**
     * Cancel the reservation.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Reservation $reservation)
    {
        // Check if reservation is already fulfilled or cancelled
        if ($reservation->status !== 'active') {
            return redirect()->route('reservations.index')
                ->with('error', 'Reservation is not active.');
        }

        $reservation->update([
            'status' => 'cancelled',
        ]);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation cancelled successfully.');
    }

    /**
     * Remove the specified reservation from storage.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation deleted successfully.');
    }
}