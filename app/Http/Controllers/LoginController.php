<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $authenticated = Auth::attempt([
            'email' => strtolower($input['email']),
            'password' => $input['password'],
        ]);

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials are not valid.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('employees.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have signed out.');
    }
}
