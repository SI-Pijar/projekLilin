<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WajibGantiSandi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->sandi_sementara && ! $request->routeIs('profil.edit', 'profil.sandi', 'keluar')) {
            return redirect()->route('profil.edit')->with('sukses', 'Anda masuk dengan sandi sementara dari admin. Ganti kata sandi sekarang untuk melanjutkan.');
        }

        return $next($request);
    }
}