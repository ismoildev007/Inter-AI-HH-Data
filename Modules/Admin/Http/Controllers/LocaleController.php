<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Persist the chosen locale in the session and apply it to the current request.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $availableLocales = ['uz', 'ru', 'en'];

        if (in_array($locale, $availableLocales, true)) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }

        return redirect()->back();
    }
}
