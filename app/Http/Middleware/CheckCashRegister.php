<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCashRegister
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user) {
            // One shared galla/register stays active for both admin and cashiers.
            $activeRegister = CashRegister::active();

            // Exclude the register-related routes from redirect loop
            $excludedRoutes = [
                'cash-registers.open',
                'cash-registers.store',
                'logout'
            ];

            if (!$activeRegister && !$request->routeIs($excludedRoutes)) {
                return redirect()->route('cash-registers.open')
                    ->with('error', 'Please open a Cash Register (enter opening float) to start transaction processing.');
            }
        }

        return $next($request);
    }
}
