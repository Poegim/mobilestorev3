<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;

class EnsureShopSelected
{
    public function handle(Request $request, Closure $next)
    {
        $shop = $request->route('shop');

        if ($shop instanceof Shop) {
            $user = $request->user();

            if ($user && !$user->hasAccessToShop($shop)) {
                abort(403);
            }

            app()->instance('currentShop', $shop);
        }

        return $next($request);
    }
}