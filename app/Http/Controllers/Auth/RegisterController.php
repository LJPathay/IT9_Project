<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        // Log the request data for debugging
        Log::info('Registration attempt with data:', $request->all());
        
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'contact_number' => 'required|string|max:255',
            ]);
            
            Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            throw $e;
        }

        // Begin transaction to ensure both records are created or none
        DB::beginTransaction();

        try {
            // Create full name for the users table
            $fullName = trim($request->first_name . ' ' . 
                        ($request->middle_name ? $request->middle_name . ' ' : '') . 
                        $request->last_name);
            
            Log::info('Creating user with name: ' . $fullName);

            // Create user record
            $user = User::create([
                'name' => $fullName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            
            Log::info('User created with ID: ' . $user->id);

            // Create member record with the same ID as the user
            $member = Member::create([
                'member_id' => $user->id, // Use the same ID as the user
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'contact_number' => $request->contact_number,
                'join_date' => Carbon::now()->toDateString(),
            ]);
            
            Log::info('Member created with ID: ' . $member->member_id);

            // Commit the transaction
            DB::commit();
            Log::info('Transaction committed successfully');

            // Log in the user
            Auth::login($user);
            Log::info('User logged in');

            return redirect('dashboard')->with('success', 'Registration successful!');
        } catch (\Exception $e) {
            // Something went wrong, rollback the transaction
            DB::rollBack();
            
            Log::error('Registration failed with exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }
}