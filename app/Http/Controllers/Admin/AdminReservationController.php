<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class AdminReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', \App\Http\Middleware\AdminMiddleware::class]);
    }

    public function index(Request $request)
    {
        $query = Reservation::with(['book', 'member']);

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
                case 'book':
                    $query->join('books', 'reservations.book_id', '=', 'books.book_id')
                          ->orderBy('books.book_title', 'asc');
                    break;
                case 'member':
                    $query->join('members', 'reservations.member_id', '=', 'members.member_id')
                          ->orderBy('members.first_name', 'asc')
                          ->orderBy('members.last_name', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $reservations = $query->get()->map(function ($reservation) {
            return [
                'reservation_id' => 'RES-' . str_pad($reservation->reservation_id, 6, '0', STR_PAD_LEFT),
                'book_title' => $reservation->book->book_title ?? 'Unknown Book',
                'member' => $reservation->member ? 
                    ($reservation->member->first_name . ' ' . $reservation->member->last_name) : 
                    'Unknown Member',
                'request_date' => $reservation->created_at->format('M d, Y'),
                'status' => ucfirst($reservation->status)
            ];
        });

        return view('admin.reservations.index', compact('reservations'));
    }

    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        
        if ($request->status === 'accepted') {
            $reservation->status = 'ready';
            $message = 'Reservation accepted successfully.';
        } elseif ($request->status === 'denied') {
            $reservation->status = 'cancelled';
            $message = 'Reservation denied successfully.';
        } else {
            return redirect()->back()->with('error', 'Invalid status provided.');
        }
        
        $reservation->save();
        
        return redirect()->back()->with('success', $message);
    }
} 