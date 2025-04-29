<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
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
     * Show the application d   ashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
{
    $user = auth()->user(); // Get the authenticated user
    return view('dashboard', compact('user'));
}
}   