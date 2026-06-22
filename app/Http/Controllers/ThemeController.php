<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:default,ocean,forest,sunset,rose,slate',
        ]);

        auth()->user()->update(['theme_preference' => $validated['theme']]);

        return response()->json(['ok' => true]);
    }
}
