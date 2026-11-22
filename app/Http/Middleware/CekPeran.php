<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CekPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('masuk');
        }

        if ($user->status !== 'aktif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('masuk')->withErrors(['email' => 'Akun Anda sedang dinonaktifkan admin.']);
        }

        if (! in_array($user->role, $peran, true)) {
            abort(403, 'Halaman ini khusus ' . implode(' atau ', $peran) . '.');
        }

        return $next($request);
    }
}