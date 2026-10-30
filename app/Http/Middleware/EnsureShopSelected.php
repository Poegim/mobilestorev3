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
                session()->flash('toast', [
                    'duration' => false,
                    'heading' => 'Brak dostępu',
                    'text'    => 'Nie masz uprawnień do sklepu "' . $shop->name . '".',
                    'variant' => 'danger',
                ]);

                return redirect()->route('dashboard');
            }

            app()->instance('currentShop', $shop);
        }

        return $next($request);
    }
}