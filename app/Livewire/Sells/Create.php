<?php

namespace App\Livewire\Sells;

use App\Enums\ItemStatus;
use App\Enums\PaymentMethod;
use App\Models\Item;
use App\Models\Sell;
use App\Models\SoldItem;
use App\Models\Service;
use App\Models\Shop;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    public Shop $shop;

    // Cart: array of ['type'=>'item'|'service', 'item_id'=>int|null, 'service_id'=>int|null,
    //                  'name'=>string, 'price'=>int(grosze), 'tax_id'=>int, 'internal_cost'=>int]
    public array $cart = [];

    // Item search
    public string $itemSearch = '';

    // Service search
    public string $serviceSearch = '';

    // Payment
    public int $paymentMethod = 1;

    // Price / tax override for currently adding item
    public string $priceOverride = '';
    public ?int $taxOverride = null;

    public function mount(Shop $shop): void
    {
        $this->shop = $shop;
    }

    // ─── Item search ─────────────────────────────────────────

    #[Computed]
    public function itemResults()
    {
        $search = trim($this->itemSearch);
        if (strlen($search) < 1) {
            return collect();
        }

        $query = Item::where('parent_shop_id', $this->shop->id)
            ->where('status', ItemStatus::Store)
            ->with(['product.brand', 'variant.devices', 'condition', 'color']);

        // Barcode lookup (12-digit code starting with 1)
        $byBarcode = Item::findByBarcode($search);
        if ($byBarcode && $byBarcode->parent_shop_id === $this->shop->id && $byBarcode->status === ItemStatus::Store) {
            return collect([$byBarcode->load(['product.brand', 'variant.devices', 'condition', 'color'])]);
        }

        // Numeric = item ID
        if (ctype_digit($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', (int) $search);
            });
        } else {
            // Search by product name, brand, or IMEI
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product.brand', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                  ->orWhere('feature_imei', 'like', "{$search}%");
            });
        }

        // Exclude items already in cart
        $cartItemIds = collect($this->cart)
            ->where('type', 'item')
            ->pluck('item_id')
            ->filter()
            ->all();

        if (!empty($cartItemIds)) {
            $query->whereNotIn('id', $cartItemIds);
        }

        return $query->limit(20)->get();
    }

    // ─── Service search ──────────────────────────────────────

    #[Computed]
    public function serviceResults()
    {
        $search = trim($this->serviceSearch);
        if (strlen($search) < 2) {
            return collect();
        }

        return Service::where('name', 'like', "%{$search}%")
            ->with('tax')
            ->limit(20)
            ->get();
    }

    // ─── Lookups ─────────────────────────────────────────────

    #[Computed]
    public function taxes()
    {
        return Tax::orderBy('id')->get();
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::cases();
    }

    // ─── Add / Remove ────────────────────────────────────────

    public function addItemToCart(int $itemId): void
    {
        // Prevent duplicates
        if (collect($this->cart)->where('type', 'item')->where('item_id', $itemId)->isNotEmpty()) {
            return;
        }

        $item = Item::where('id', $itemId)
            ->where('parent_shop_id', $this->shop->id)
            ->where('status', ItemStatus::Store)
            ->with(['product.brand', 'variant', 'purchasedItem'])
            ->first();

        if (!$item) {
            $this->addError('itemSearch', 'Nie znaleziono przedmiotu lub jest niedostępny.');
            return;
        }

        // Default price from pricing system
        $defaultPrice = $item->getSellingPrice() ?? 0;

        // Use override if set, otherwise default
        $price = $this->priceOverride !== ''
            ? (int) round((float) $this->priceOverride * 100)
            : $defaultPrice;

        // Tax: if item was purchased with VAT invoice → suggest same tax;
        // otherwise default to 0 (margin/exempt)
        $taxId = $this->taxOverride ?? ($item->purchasedItem?->tax_id ?? 0);

        $this->cart[] = [
            'type' => 'item',
            'item_id' => $item->id,
            'service_id' => null,
            'name' => $item->product?->display_name ?? "Item #{$item->id}",
            'detail' => $item->variant?->display_name,
            'imei' => $item->feature_imei,
            'condition' => $item->condition?->name,
            'price' => $price,
            'tax_id' => $taxId,
            'internal_cost' => 0,
            'purchase_gross' => $item->purchasedItem?->getGrossPrice() ?? 0,
        ];

        $this->itemSearch = '';
        $this->priceOverride = '';
        $this->taxOverride = null;
    }

    public function addServiceToCart(int $serviceId): void
    {
        $service = Service::with('tax')->find($serviceId);
        if (!$service) return;

        $this->cart[] = [
            'type' => 'service',
            'item_id' => null,
            'service_id' => $service->id,
            'name' => $service->name,
            'detail' => null,
            'imei' => null,
            'condition' => null,
            'price' => $service->price,
            'tax_id' => $service->tax_id ?? 0,
            'internal_cost' => $service->internal_cost ?? 0,
            'purchase_gross' => 0,
        ];

        $this->serviceSearch = '';
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function updateCartPrice(int $index, string $value): void
    {
        if (!isset($this->cart[$index])) return;
        $this->cart[$index]['price'] = (int) round((float) $value * 100);
    }

    public function updateCartTax(int $index, int $taxId): void
    {
        if (!isset($this->cart[$index])) return;
        $this->cart[$index]['tax_id'] = $taxId;
    }

    // ─── Totals ──────────────────────────────────────────────

    #[Computed]
    public function cartTotal(): int
    {
        return collect($this->cart)->sum('price');
    }

    #[Computed]
    public function cartCount(): int
    {
        return count($this->cart);
    }

    // ─── Confirm ─────────────────────────────────────────────

    public function confirm(): void
    {
        if (empty($this->cart)) {
            $this->addError('confirm', 'Dodaj przynajmniej jedną pozycję.');
            return;
        }

        // Verify all items are still available
        $itemIds = collect($this->cart)->where('type', 'item')->pluck('item_id')->filter()->all();
        if (!empty($itemIds)) {
            $available = Item::whereIn('id', $itemIds)
                ->where('parent_shop_id', $this->shop->id)
                ->where('status', ItemStatus::Store)
                ->count();

            if ($available !== count($itemIds)) {
                $this->addError('confirm', 'Niektóre przedmioty nie są już dostępne. Odśwież stronę.');
                return;
            }
        }

        DB::transaction(function () {
            $sell = Sell::create([
                'parent_shop_id' => $this->shop->id,
                'valid' => true,
                'payment_method' => $this->paymentMethod,
            ]);

            foreach ($this->cart as $entry) {
                SoldItem::create([
                    'sell_id' => $sell->id,
                    'item_id' => $entry['item_id'] ?? 0,
                    'service_id' => $entry['service_id'] ?? 0,
                    'price' => $entry['price'],
                    'internal_cost' => $entry['internal_cost'],
                    'tax_id' => $entry['tax_id'],
                    'valid' => true,
                ]);

                // Mark item as sold
                if ($entry['item_id']) {
                    Item::where('id', $entry['item_id'])->update([
                        'status' => ItemStatus::Sold,
                    ]);
                }
            }

            session()->flash('toast', [
                'duration' => false,
                'heading' => 'Sprzedaż zapisana',
                'text' => "Sprzedaż #{$sell->id} — {$this->cartCount} pozycji, {$this->formatPrice($this->cartTotal)}",
                'variant' => 'success',
            ]);

            $this->redirectRoute('shop.sells.show', ['shop' => $this->shop, 'sell' => $sell], navigate: true);
        });
    }

    // ─── Helpers ──────────────────────────────────────────────

    private function formatPrice(int $grosze): string
    {
        return number_format($grosze / 100, 2, ',', ' ') . ' zł';
    }

    public function render()
    {
        return view('livewire.sells.create')
            ->title('Nowa sprzedaż');
    }
}