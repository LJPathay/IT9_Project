<?php

namespace App\Http\Controllers;

use App\Models\FeeType;
use Illuminate\Http\Request;

class FeeTypeController extends Controller
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
     * Display a listing of the fee types.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $feeTypes = FeeType::all();
        return view('fee_types.index', compact('feeTypes'));
    }

    /**
     * Show the form for creating a new fee type.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('fee_types.create');
    }

    /**
     * Store a newly created fee type in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:fee_types',
        ]);

        FeeType::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('fee-types.index')
            ->with('success', 'Fee type created successfully.');
    }

    /**
     * Display the specified fee type.
     *
     * @param  \App\Models\FeeType  $feeType
     * @return \Illuminate\View\View
     */
    public function show(FeeType $feeType)
    {
        return view('fee_types.show', compact('feeType'));
    }

    /**
     * Show the form for editing the specified fee type.
     *
     * @param  \App\Models\FeeType  $feeType
     * @return \Illuminate\View\View
     */
    public function edit(FeeType $feeType)
    {
        return view('fee_types.edit', compact('feeType'));
    }

    /**
     * Update the specified fee type in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FeeType  $feeType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, FeeType $feeType)
    {
        $request->validate([
            'name' => 'required|unique:fee_types,name,' . $feeType->id,
        ]);

        $feeType->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('fee-types.index')
            ->with('success', 'Fee type updated successfully.');
    }

    /**
     * Remove the specified fee type from storage.
     *
     * @param  \App\Models\FeeType  $feeType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(FeeType $feeType)
    {
        // Check if fee type has transactions before deletion
        if ($feeType->transactions()->count() > 0) {
            return redirect()->route('fee-types.index')
                ->with('error', 'Cannot delete fee type with associated transactions.');
        }

        $feeType->delete();

        return redirect()->route('fee-types.index')
            ->with('success', 'Fee type deleted successfully.');
    }
}