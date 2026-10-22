<?php

namespace App\Livewire\Purchases;

use App\Enums\ItemStatus;
use App\Enums\PaymentMethod;
use App\Models\Color;
use App\Models\Condition;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasedItem;
use App\Models\Shop;
use App\Models\Tax;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Device;
class Create extends Component
{
    public Shop $shop;
    public ?int $purchaseId = null;
    public bool $isDevice = false;

    public string $filterBrand = '';
    public string $filterCategory = '';
    public string $filterDevice = '';
    public ?int $filterDeviceId = null;
    public ?string $filterDeviceName = null;

    // Purchase header
    public int $step = 1;
    public string $contactSearch = '';
    public ?int $contactId = null;
    public ?string $contactName = null;
    public string $invoiceNumber = '';
    public int $paymentMethod = 1;
    public ?int $purchaseType = null;
    // Item form
    public int $quantity = 1;
    public string $productSearch = '';
    public ?int $productId = null;
    public ?string $productName = null;
    public ?int $conditionId = null;
    public string $priceNet = '';
    public string $priceSell = '';
    public ?int $taxId = null;
    public ?int $colorId = null;
    public string $featureImei = '';
    public ?float $featureStorage = null;
    public bool $featureBox = false;
    public bool $featureProofOfPurchase = false;
    public string $featureComment = '';

    public function mount(Shop $shop): void
    {
        $this->shop = $shop;

        // Resume existing draft if any
        $draft = Purchase::where('parent_shop_id', $shop->id)
            ->where('created_by', auth()->id())
            ->where('is_draft', true)
            ->first();

        if ($draft) {
            $this->purchaseId = $draft->id;
            $this->contactId = $draft->contact_id;
            $this->contactName = $draft->contact?->name;
            $this->invoiceNumber = $draft->invoice_number ?? '';
            $this->paymentMethod = $draft->payment_method?->value ?? 1;
            $this->purchaseType = $draft->purchase_type->value;
            $this->step = 3;
            $this->conditionId = 1;
            $this->setDefaultTax();
        }

        $this->conditionId = 1;
        
    }

    private function setDefaultTax(): void
    {
        $this->taxId = match ($this->purchaseType) {
            2 => Tax::where('percentage', 0)->first()?->id,
            default => Tax::where('percentage', 23)->first()?->id,
        };
    }

    #[Computed]
    public function deviceFilterResults()
    {
        if (strlen($this->filterDevice) < 2) {
            return collect();
        }

        $search = $this->filterDevice;

        return Device::with('brand')
            ->leftJoin('brands', 'devices.brand_id', '=', 'brands.id')
            ->where(function ($q) use ($search) {
                $q->where('devices.model', 'like', "%{$search}%")
                  ->orWhere('brands.name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(COALESCE(brands.name, ''), ' ', devices.model) LIKE ?", ["%{$search}%"]);
            })
            ->select('devices.*')
            ->orderBy('brands.name')
            ->orderBy('devices.model')
            ->limit(20)
            ->get();
    }

    public function selectDeviceFilter(int $id): void
    {
        $device = Device::with('brand')->find($id);
        if (!$device) return;

        $this->filterDeviceId = $device->id;
        $this->filterDeviceName = $device->full_name;
        $this->filterDevice = '';
    }

    public function clearDeviceFilter(): void
    {
        $this->filterDeviceId = null;
        $this->filterDeviceName = null;
        $this->filterDevice = '';
    }

    #[Computed]
    public function contactResults()
    {
        if (strlen($this->contactSearch) < 2) {
            return collect();
        }

        return Contact::where('name', 'like', "%{$this->contactSearch}%")
            ->orWhere('identity_number', 'like', "%{$this->contactSearch}%")
            ->orderBy('name')
            ->limit(15)
            ->get();
    }

    #[Computed]
    public function brands()
    {
        return \App\Models\Brand::orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return \App\Models\Category::orderBy('name')->get();
    }

    #[Computed]
    public function productResults()
    {
        if (strlen($this->productSearch) < 2 && !$this->filterBrand && !$this->filterCategory && !$this->filterDeviceId) {
            return collect();
        }

        $query = Product::current()
            ->with(['brand', 'devices', 'category'])
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select('products.*');

        if ($this->productSearch !== '' && strlen($this->productSearch) >= 2) {
            $search = $this->productSearch;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(COALESCE(brands.name, ''), ' ', products.name) LIKE ?", ["%{$search}%"])
                  ->orWhere('products.name', 'like', "%{$search}%")
                  ->orWhere('brands.name', 'like', "%{$search}%")
                  ->orWhereHas('devices', fn ($d) => $d->where('model', 'like', "%{$search}%"));
            });
        }

        if ($this->filterBrand !== '') {
            $query->where('products.brand_id', (int) $this->filterBrand);
        }

        if ($this->filterCategory !== '') {
            $query->where('products.parent_category_id', (int) $this->filterCategory);
        }

        if ($this->filterDeviceId) {
            $query->whereHas('devices', fn ($d) => $d->where('devices.id', $this->filterDeviceId));
        }

        return $query
            ->orderBy('brands.name')
            ->orderBy('products.name')
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function conditions()
    {
        return Condition::active()->orderBy('id')->get();
    }

    #[Computed]
    public function taxes()
    {
        return Tax::orderBy('id')->get();
    }

    #[Computed]
    public function colors()
    {
        return Color::orderBy('name')->get();
    }

    #[Computed]
    public function draftItems()
    {
        if (!$this->purchaseId) {
            return collect();
        }

        return PurchasedItem::where('purchase_id', $this->purchaseId)
            ->with(['item.product.brand', 'item.product.devices', 'item.color', 'item.condition', 'tax'])
            ->get();
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::cases();
    }

    public function selectContact(int $id): void
    {
        $contact = Contact::find($id);
        if (!$contact) return;

        $this->contactId = $contact->id;
        $this->contactName = $contact->name;
        $this->contactSearch = '';

        $this->saveDraftHeader();
    }

    public function nextStep(): void
    {
        if ($this->step === 2) {
            $this->validate([
                'contactId' => 'required',
            ], [
                'contactId.required' => 'Wybierz dostawcę.',
            ]);

            $this->saveDraftHeader();
        }

        $this->step++;
    }

    public function backToHeader(): void
    {
        $this->step = 2;
    }

    public function clearContact(): void
    {
        $this->contactId = null;
        $this->contactName = null;
    }

    public function selectProduct(int $id): void
    {
        $product = Product::with(['brand', 'devices'])->find($id);
        if (!$product) return;

        $this->productId = $product->id;
        $this->productName = $product->display_name;
        $this->productSearch = '';
        // Check if product is a device (category 2 or child of 2)
        $categoryId = $product->parent_category_id;
        $this->isDevice = $categoryId === 2
        || \App\Models\Category::where('id', $categoryId)->where('parent_category_id', 2)->exists();

        $this->productSearch = '';
        $this->filterBrand = '';
        $this->filterCategory = '';
        $this->clearDeviceFilter();

    }

    public function clearProduct(): void
    {
        $this->productId = null;
        $this->productName = null;
        $this->isDevice = false;
        $this->featureImei = '';
        $this->featureStorage = null;
    }

    public function updatedPaymentMethod(): void
    {
        $this->saveDraftHeader();
    }

    public function updatedInvoiceNumber(): void
    {
        $this->saveDraftHeader();
    }

    public function updatedPurchaseType(): void
    {
        $this->setDefaultTax();
        $this->conditionId = 1;
        $this->step = 2;
    }

    /** Ensure a draft purchase record exists and update header fields */
    private function saveDraftHeader(): void
    {
        if (!$this->purchaseId) {
            $purchase = Purchase::create([
                'parent_shop_id' => $this->shop->id,
                'contact_id' => $this->contactId,
                'invoice_number' => $this->invoiceNumber ?: null,
                'payment_method' => $this->paymentMethod,
                'purchase_type' => $this->purchaseType,
                'valid' => true,
                'is_draft' => true,
                'created_by' => auth()->id(),
            ]);
            $this->purchaseId = $purchase->id;
        } else {
            Purchase::where('id', $this->purchaseId)->update([
                'purchase_type' => $this->purchaseType,
                'contact_id' => $this->contactId,
                'invoice_number' => $this->invoiceNumber ?: null,
                'payment_method' => $this->paymentMethod,
            ]);
        }
    }

    public function addItem(): void
    {
        $this->validate([
            'quantity' => 'required|integer|min:1|max:200',
            'productId' => 'required|exists:products,id',
            'conditionId' => 'required|exists:conditions,id',
            'priceNet' => 'required|numeric|gt:0',
            'taxId' => 'required|exists:taxes,id',
        ], [
            'quantity.min' => 'Ilość musi wynosić co najmniej 1.',
            'productId.required' => 'Wybierz produkt.',
            'conditionId.required' => 'Wybierz stan.',
            'priceNet.required' => 'Wpisz cenę netto.',
            'taxId.required' => 'Wybierz stawkę VAT.',
            'priceNet.gt' => 'Cena musi być większa od zera.',
        ]);

        // Ensure draft exists
        $this->saveDraftHeader();

        // Price in grosze
        $priceGrosze = (int) round((float) $this->priceNet * 100);

        // Devices are always single units (unique IMEI); accessories can be added in bulk
        $qty = $this->isDevice ? 1 : $this->quantity;

        DB::transaction(function () use ($priceGrosze, $qty) {
            for ($i = 0; $i < $qty; $i++) {
                $item = Item::create([
                    'parent_shop_id' => $this->shop->id,
                    'product_id' => $this->productId,
                    'status' => ItemStatus::Draft,
                    'feature_condition_id' => $this->conditionId,
                    'feature_color_id' => $this->colorId,
                    'feature_imei' => $this->featureImei ?: null,
                    'feature_storage' => $this->featureStorage,
                    'feature_box' => $this->featureBox,
                    'feature_proof_of_purchase' => $this->featureProofOfPurchase,
                    'feature_comment' => $this->featureComment ?: null,
                    'feature_price' => $this->priceSell !== '' ? (int) round((float) $this->priceSell * 100) : null,
                ]);

                PurchasedItem::create([
                    'purchase_id' => $this->purchaseId,
                    'item_id' => $item->id,
                    'tax_id' => $this->taxId,
                    'price' => $priceGrosze,
                ]);
            }
        });

        // Reset item form, keep product for quick re-add
        $this->resetItemForm();
    }

    public function removeItem(int $purchasedItemId): void
    {
        $pi = PurchasedItem::where('id', $purchasedItemId)
            ->where('purchase_id', $this->purchaseId)
            ->first();

        if (!$pi) return;

        DB::transaction(function () use ($pi) {
            $itemId = $pi->item_id;
            $pi->delete();
            Item::where('id', $itemId)->where('status', ItemStatus::Draft)->delete();
        });
    }

    public function confirm(): void
    {
        if (!$this->purchaseId) return;

        $purchase = Purchase::find($this->purchaseId);
        if (!$purchase || !$purchase->is_draft) return;

        if ($purchase->purchasedItems()->count() === 0) {
            $this->addError('confirm', 'Dodaj przynajmniej jeden przedmiot.');
            return;
        }

        DB::transaction(function () use ($purchase) {
            // Finalize purchase
            $purchase->update(['is_draft' => false]);

            // Change all draft items to store status
            $itemIds = $purchase->purchasedItems()->pluck('item_id');
            Item::whereIn('id', $itemIds)
                ->where('status', ItemStatus::Draft)
                ->update([
                    'status' => ItemStatus::Store,
                    'displaced_at' => now(),
                ]);
        });

        session()->flash('toast', [
            'heading' => 'Zakup potwierdzony',
            'text' => "Zakup #{$purchase->id} — dodano {$purchase->purchasedItems()->count()} przedmiotów",
            'variant' => 'success',
        ]);

        $this->redirectRoute('shop.purchases.index', ['shop' => $this->shop], navigate: true);
    }

    /** Delete the current draft purchase together with its draft items. */
    private function deleteDraft(): void
    {
        if (!$this->purchaseId) {
            return;
        }

        $purchase = Purchase::where('id', $this->purchaseId)
            ->where('is_draft', true)
            ->first();

        if (!$purchase) {
            return;
        }

        DB::transaction(function () use ($purchase) {
            $itemIds = $purchase->purchasedItems()->pluck('item_id');
            $purchase->purchasedItems()->delete();
            Item::whereIn('id', $itemIds)->where('status', ItemStatus::Draft)->delete();
            $purchase->delete();
        });
    }

    public function cancelDraft(): void
    {
        $this->deleteDraft();

        session()->flash('toast', [
            'heading' => 'Wersja robocza usunięta',
            'variant' => 'warning',
        ]);

        $this->redirectRoute('shop.purchases.index', ['shop' => $this->shop], navigate: true);
    }

    /** Discard the draft and start over from the purchase type selection. */
    public function restart(): void
    {
        $this->deleteDraft();

        // Restore every form property to its class default
        $this->reset([
            'purchaseId', 'purchaseType', 'step',
            'contactId', 'contactName', 'contactSearch',
            'invoiceNumber', 'paymentMethod',
            'productId', 'productName', 'productSearch',
            'filterBrand', 'filterCategory', 'filterDevice', 'filterDeviceId', 'filterDeviceName',
            'isDevice', 'priceNet', 'taxId', 'colorId', 'conditionId',
            'featureImei', 'featureStorage', 'featureBox', 'featureProofOfPurchase', 'featureComment',
        ]);

        $this->resetValidation();
    }

    private function resetItemForm(): void
    {
        $this->priceNet = '';
        $this->quantity = 1;
        $this->isDevice = false;
        $this->featureImei = '';
        $this->colorId = null;
        $this->featureStorage = null;
        $this->featureBox = false;
        $this->featureProofOfPurchase = false;
        $this->featureComment = '';
        $this->priceSell = '';
        
        // Keep conditionId and taxId — usually the same for a batch
    }

    public function render()
    {
        return view('livewire.purchases.create')
            ->title('Nowy zakup');
    }
}