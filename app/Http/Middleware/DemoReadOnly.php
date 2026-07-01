<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoReadOnly
{
    /**
     * Bloquea cualquier operación de escritura para los tokens de demo.
     *
     * Un token de demo se emite con la ability 'demo' (ver UserController::demoLogin).
     * Los tokens normales se emiten con '*', por lo que su array de abilities
     * NO contiene literalmente 'demo' y nunca se ven afectados por este middleware.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        $isDemo = $token && in_array('demo', $token->abilities ?? [], true);

        // Permitimos métodos seguros (GET/HEAD/OPTIONS) y el logout.
        if ($isDemo && ! $request->isMethodSafe() && ! $request->is('api/logout')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Modo demo: solo lectura. Esta acción está deshabilitada.',
            ], 403);
        }

        return $next($request);
    }
}
