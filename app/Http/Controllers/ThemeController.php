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

    public function updateCardStyle(Request $request)
    {
        $validated = $request->validate([
            'card_style' => 'required|in:default,bordered,shadow',
        ]);

        auth()->user()->update(['card_style' => $validated['card_style']]);

        return response()->json(['ok' => true]);
    }
}
