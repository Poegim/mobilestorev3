<?php

namespace App\Livewire\Transfers;

use App\Enums\TransferStatus;
use App\Models\Shop;
use App\Models\Transfer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?Shop $shop = null;

    /** Only used inside a shop: all | out | in */
    #[Url]
    public string $direction = 'all';

    /** Default: only transfers in progress ("1" = TransferStatus::Active); empty string = all */
    #[Url]
    public string $status = '1';

    #[Url]
    public int $perPage = 25;

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    public function updatedDirection(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function statuses(): array
    {
        return TransferStatus::cases();
    }

    private function applyFilters($query)
    {
        if ($this->shop) {
            $shopId = $this->shop->id;

            match ($this->direction) {
                'out'   => $query->where('parent_shop_id', $shopId),
                'in'    => $query->where('target_shop_id', $shopId),
                default => $query->where(fn ($q) => $q
                    ->where('parent_shop_id', $shopId)
                    ->orWhere('target_shop_id', $shopId)),
            };
        } else {
            $user = auth()->user();

            // Non-admins see only transfers touching their own shops
            if (! $user->isAdmin()) {
                $shopIds = $user->shops()->pluck('shops.id');
                $query->where(fn ($q) => $q
                    ->whereIn('parent_shop_id', $shopIds)
                    ->orWhereIn('target_shop_id', $shopIds));
            }
        }

        if ($this->status !== '') {
            $query->where('status', (int) $this->status);
        }

        return $query;
    }

    public function render()
    {
        $query = Transfer::query()
            ->with(['sourceShop', 'targetShop', 'creator'])
            ->withCount('transferItems');

        $transfers = $this->applyFilters($query)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.transfers.index', [
            'transfers' => $transfers,
        ]);
    }
}