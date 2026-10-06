<?php

namespace App\Livewire\Transfers;

use App\Enums\ItemStatus;
use App\Enums\TransferStatus;
use App\Models\Item;
use App\Models\Shop;
use App\Models\Transfer;
use App\Models\TransferCartItem;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    public Shop $shop;

    public string $barcode = '';

    public ?int $targetShopId = null;

    public function mount(Shop $shop): void
    {
        $this->shop = $shop;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, TransferCartItem> */
    #[Computed]
    public function cartLines()
    {
        return TransferCartItem::forUserInShop(auth()->user(), $this->shop)
            ->with(['item.product.brand', 'item.condition'])
            ->latest('id')
            ->get();
    }

    /** Active shops that can receive the transfer (everything except the source shop). */
    #[Computed]
    public function targetShops()
    {
        return Shop::where('archive', false)
            ->where('id', '!=', $this->shop->id)
            ->orderBy('order')
            ->get();
    }

    /**
     * Cart lines whose item is no longer transferable (sold, already in transfer, moved...).
     * Returns [item_id => human readable reason].
     *
     * @return array<int, string>
     */
    #[Computed]
    public function unavailableReasons(): array
    {
        $reasons = [];

        foreach ($this->cartLines as $line) {
            $item = $line->item;

            if (! $item) {
                $reasons[$line->item_id] = 'Brak itemu';
            } elseif ($item->status !== ItemStatus::Store) {
                $reasons[$line->item_id] = $item->status->label();
            } elseif ((int) $item->parent_shop_id !== $this->shop->id) {
                $reasons[$line->item_id] = 'Inny sklep';
            }
        }

        return $reasons;
    }

    public function addItem(): void
    {
        $this->validate(
            ['barcode' => 'required|string'],
            ['barcode.required' => 'Zeskanuj kod lub wpisz ID itema.']
        );

        $item = $this->resolveItem($this->barcode);
        $this->barcode = '';

        if (! $item) {
            $this->addError('barcode', 'Nieprawidłowy kod lub item nie istnieje.');
            return;
        }

        // Only items on the shelf of this very shop can be transferred
        if ($item->status !== ItemStatus::Store || (int) $item->parent_shop_id !== $this->shop->id) {
            $this->addError('barcode', "Item #{$item->id} nie jest dostępny na magazynie tego sklepu.");
            return;
        }

        $line = TransferCartItem::firstOrCreate([
            'user_id'        => auth()->id(),
            'parent_shop_id' => $this->shop->id,
            'item_id'        => $item->id,
        ]);

        if (! $line->wasRecentlyCreated) {
            $this->addError('barcode', "Item #{$item->id} jest już w koszyku.");
        }
    }

    public function removeItem(int $lineId): void
    {
        // Scoped to the current user and shop, so nobody can delete another cart's line
        TransferCartItem::forUserInShop(auth()->user(), $this->shop)
            ->whereKey($lineId)
            ->delete();
    }

    /** Turn the cart into an active transfer. */
    public function send()
    {
        $this->validate(
            [
                'targetShopId' => [
                    'required',
                    'integer',
                    Rule::exists('shops', 'id')->where('archive', false),
                    Rule::notIn([$this->shop->id]),
                ],
            ],
            [
                'targetShopId.required' => 'Wybierz sklep docelowy.',
                'targetShopId.exists'   => 'Wybrany sklep nie istnieje lub jest zarchiwizowany.',
                'targetShopId.not_in'   => 'Sklep docelowy musi być inny niż źródłowy.',
            ]
        );

        try {
            $transfer = DB::transaction(function () {
                $itemIds = TransferCartItem::forUserInShop(auth()->user(), $this->shop)->pluck('item_id');

                if ($itemIds->isEmpty()) {
                    throw new DomainException('Koszyk jest pusty.');
                }

                // Lock the rows so a parallel transfer or sale has to wait for us
                $items = Item::whereIn('id', $itemIds)->lockForUpdate()->get();

                if ($items->count() !== $itemIds->count()) {
                    throw new DomainException('Część itemów z koszyka nie istnieje.');
                }

                // Re-check availability on fresh, locked data (the cart may be stale)
                $unavailable = $items->first(
                    fn (Item $i) => $i->status !== ItemStatus::Store
                        || (int) $i->parent_shop_id !== $this->shop->id
                );

                if ($unavailable) {
                    throw new DomainException("Item #{$unavailable->id} nie jest już dostępny. Usuń go z koszyka.");
                }

                $transfer = Transfer::create([
                    'parent_shop_id' => $this->shop->id,
                    'target_shop_id' => $this->targetShopId,
                    'created_by'     => auth()->id(),
                    'status'         => TransferStatus::Active,
                ]);

                $transfer->transferItems()->createMany(
                    $items->map(fn (Item $i) => ['item_id' => $i->id])->all()
                );

                Item::whereIn('id', $itemIds)->update(['status' => ItemStatus::Transfer->value]);

                TransferCartItem::forUserInShop(auth()->user(), $this->shop)->delete();

                return $transfer;
            });
        } catch (DomainException $e) {
            $this->addError('send', $e->getMessage());
            return;
        }

        // Flashed for exactly one request; the layout turns it into a toast
        session()->flash('toast', [
            'heading' => 'Transfer wysłany',
            'text'    => "Transfer #{$transfer->id} ({$transfer->transferItems()->count()} szt.) jest w drodze.",
            'variant' => 'success',
        ]);

        return $this->redirectRoute('shop.transfers.index', $this->shop);
    }

    /** Accepts either a plain item ID (up to 8 digits) or a full 12-digit barcode. */
    private function resolveItem(string $input): ?Item
    {
        $input = trim($input, " \t\n\r\0\x0B@");

        if (preg_match('/^[0-9]{1,8}$/', $input)) {
            return Item::find((int) $input);
        }

        return Item::findByBarcode($input);
    }

    public function render()
    {
        return view('livewire.transfers.create');
    }
}