<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('member_id', 'like', "%{$search}%");
            });
        }

        $members = $query->paginate(10);
        return view('admin.members.index', compact('members'));
    }

    public function edit(Member $member)
    {
        return view('admin.members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email,' . $member->member_id . ',member_id',
            'contact_number' => 'required|string|max:20',
            'address' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $member->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'status' => $request->status,
        ]);

        if ($request->filled('password')) {
            $member->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return redirect()->route('admin.members.index')
            ->with('success', 'Member updated successfully.');
    }

    public function destroy(Member $member)
    {
        // Check if member has any active loans or reservations
        if ($member->loans()->where('status', 'active')->exists() || 
            $member->reservations()->where('status', 'pending')->exists()) {
            return redirect()->route('admin.members.index')
                ->with('error', 'Cannot delete member with active loans or reservations.');
        }

        // Delete associated user account if exists
        if ($member->user) {
            $member->user->delete();
        }

        // Delete the member
        $member->delete();

        return redirect()->route('admin.members.index')
            ->with('success', 'Member account deleted successfully.');
    }
} 