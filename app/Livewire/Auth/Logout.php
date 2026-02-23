<?php

namespace App\Livewire\Auth;

use App\Services\AuthServiceClient;
use Illuminate\Http\Request;
use Livewire\Component;

class Logout extends Component
{

    /**
     * Logout: clear local session and redirect to auth service logout.
     */
    public function logout(Request $request)
    {
        $accessToken = session('auth_access_token');

        // Invalidate local cache
        if ($accessToken) {
            $authService = app(AuthServiceClient::class);
            $authService->invalidateCache($accessToken);
        }

        // Clear session
        session()->forget([
            'auth_access_token',
            'auth_user',
            'auth_token_expires',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to auth service logout (which then shows login page)
        return redirect()->away(config('auth-service.url') . '/logout');
    }

    public function render()
    {
        return view('livewire.auth.logout');
    }
}
