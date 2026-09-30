<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Family;

class FamilyController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $family = Family::create([
            'name' => trim($validated['name']),
            'user_id' => Auth::id(),
        ]);

        return response()->json($family, 201);
    }

    public function destroy(Request $request, $id)
    {
        $family = Family::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $family->delete();

        return response()->json(['success' => true]);
    }
}
