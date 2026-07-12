<?php

namespace App\Support\Dashboard;

use App\Models\Shop;
use App\Models\User;

final class DashboardScope
{
    /**
     * @param  int|null  $shopId          single shop to restrict to, or null
     * @param  int[]     $allowedShopIds  user's shops; empty = "all shops" (admin)
     */
    public function __construct(
        public readonly ?int $shopId,
        public readonly array $allowedShopIds,
    ) {}

    public static function make(?Shop $shop, User $user): self
    {
        return new self(
            shopId: $shop?->id,
            allowedShopIds: $user->isAdmin() ? [] : $user->shops()->pluck('shops.id')->all(),
        );
    }

    /**
     * Restrict a query to the shops in scope.
     *
     * @param  string  $column  shop column; qualify it (e.g. "sells.parent_shop_id") for joined queries
     */
    public function apply($query, string $column = 'parent_shop_id')
    {
        if ($this->shopId !== null) {
            return $query->where($column, $this->shopId);
        }

        if ($this->allowedShopIds !== []) {
            return $query->whereIn($column, $this->allowedShopIds);
        }

        return $query;
    }
}