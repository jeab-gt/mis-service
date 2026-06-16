<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(string $locale)
    {
        if (in_array($locale, ['th', 'en'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    }
}
