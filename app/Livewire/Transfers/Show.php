<?php

namespace App\Livewire\Transfers;

use App\Enums\ItemStatus;
use App\Enums\TransferStatus;
use App\Models\Item;
use App\Models\Shop;
use App\Models\Transfer;
use DomainException;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Transfer $transfer;

    public ?Shop $shop = null;

    private const EAGER_LOADS = [
        'sourceShop',
        'targetShop',
        'creator',
        'finisher',
    ];

    private const PER_PAGE = 25;

    public function mount(Transfer $transfer, ?Shop $shop = null): void
    {
        // Only people from one of the two shops (or admins) may open the transfer
        abort_unless(
            $this->hasAccessTo($transfer->parent_shop_id) || $this->hasAccessTo($transfer->target_shop_id),
            403
        );

        $this->transfer = $transfer->load(self::EAGER_LOADS)->loadCount('transferItems');
        $this->shop = $shop;
    }

    /** Target shop accepts the transfer: items move to the target shop. */
    public function receive(): void
    {
        abort_unless($this->mayReceive(), 403);

        $this->close(
            TransferStatus::Completed,
            [
                'parent_shop_id' => $this->transfer->target_shop_id,
                'status'         => ItemStatus::Store->value,
                // Assumption: displaced_at means "placed in this shop" (days on shelf resets)
                'displaced_at'   => now(),
            ],
            'Transfer przyjęty',
        );
    }

    /** Transfer canceled: items stay in the source shop and go back on the shelf. */
    public function cancel(): void
    {
        abort_unless($this->mayCancelOrLose(), 403);

        $this->close(
            TransferStatus::Canceled,
            ['status' => ItemStatus::Store->value],
            'Transfer anulowany',
        );
    }

    /** Transfer lost in transit: items are marked as lost. */
    public function markLost(): void
    {
        abort_unless($this->mayCancelOrLose(), 403);

        $this->close(
            TransferStatus::Lost,
            ['status' => ItemStatus::Lost->value],
            'Transfer oznaczony jako zgubiony',
        );
    }

    /**
     * Close an active transfer and apply the given attributes to all its items.
     *
     * @param  array<string, mixed>  $itemAttributes
     */
    private function close(TransferStatus $newStatus, array $itemAttributes, string $toastHeading): void
    {
        try {
            DB::transaction(function () use ($newStatus, $itemAttributes) {
                // Re-read under lock so two people cannot close the same transfer twice
                $locked = Transfer::lockForUpdate()->findOrFail($this->transfer->id);

                if ($locked->status !== TransferStatus::Active) {
                    throw new DomainException('Ten transfer został już zamknięty.');
                }

                Item::whereIn('id', $locked->transferItems()->pluck('item_id'))
                    ->update($itemAttributes);

                $locked->update([
                    'status'      => $newStatus,
                    'finished_at' => now(),
                    'finished_by' => auth()->id(),
                ]);
            });
        } catch (DomainException $e) {
            $this->addError('action', $e->getMessage());
            $this->refreshTransfer();
            return;
        }

        $this->refreshTransfer();

        Flux::modals()->close();
        Flux::toast(heading: $toastHeading, text: "Transfer #{$this->transfer->id}", variant: 'success');
    }

    private function hasAccessTo(int $shopId): bool
    {
        $user = auth()->user();

        return $user->isAdmin() || $user->shops()->whereKey($shopId)->exists();
    }

    private function mayReceive(): bool
    {
        return $this->hasAccessTo($this->transfer->target_shop_id);
    }

    private function mayCancelOrLose(): bool
    {
        return $this->hasAccessTo($this->transfer->parent_shop_id)
            || $this->hasAccessTo($this->transfer->target_shop_id);
    }

    private function refreshTransfer(): void
    {
        $this->transfer = $this->transfer->fresh(self::EAGER_LOADS)->loadCount('transferItems');
    }

    public function render()
    {
        $isActive = $this->transfer->status === TransferStatus::Active;

        return view('livewire.transfers.show', [
            'canReceive'      => $isActive && $this->mayReceive(),
            'canCancelOrLose' => $isActive && $this->mayCancelOrLose(),
            'lines'           => $this->transfer->transferItems()
                ->with(['item.product.brand', 'item.condition'])
                ->orderBy('id')
                ->paginate(self::PER_PAGE),
            'backUrl'         => $this->shop
                ? route('shop.transfers.index', $this->shop)
                : route('transfers.index'),
        ]);
    }
}