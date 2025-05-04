<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the admin login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * Handle an admin login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Simple admin credentials check - in a real application, you would use Auth::guard('admin')
        // or a more secure authentication method
        if ($request->username === 'admin' && $request->password === 'admin') {
            // Store admin authentication in the session
            $request->session()->put('admin_authenticated', true);
            
            // Redirect to admin dashboard
            return redirect()->route('admin.    ');
        }

        // If authentication fails, redirect back with error
        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('username'));
    }

    /**
     * Log the admin out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $request->session()->forget('admin_authenticated');
        
        return redirect()->route('admin.login');
    }
}