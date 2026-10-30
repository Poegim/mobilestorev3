<?php

namespace App\Livewire\Purchases;

use App\Enums\ItemStatus;
use App\Enums\PaymentMethod;
use App\Models\Color;
use App\Models\Condition;
use App\Models\Contact;
use App\Models\Device;
use App\Models\Item;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchasedItem;
use App\Models\Shop;
use App\Models\Tax;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class Create extends Component
{
    public Shop $shop;
    public ?int $purchaseId = null;

    // Steps & purchase header
    public int $step = 1;
    public ?int $purchaseType = null;
    public string $contactSearch = '';
    public ?int $contactId = null;
    public ?string $contactName = null;
    public string $invoiceNumber = '';
    public int $paymentMethod = 1;

    // Product search
    public string $productSearch = '';
    public string $filterBrand = '';
    public string $filterCategory = '';

    // Selected product
    public ?int $productId = null;
    public ?string $productName = null;
    public bool $isDevice = false;

    // Variant selection
    public string $selectedVariantId = '';
    public array $productVariants = []; // [{id, display_name}]
    public bool $showCreateVariant = false;
    public string $variantDeviceSearch = '';
    public array $newVariantDevices = []; // [{id, full_name}]

    // Item form
    public int $quantity = 1;
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

    // ─── Mount ───────────────────────────────────────────────

    public function mount(Shop $shop): void
    {
        $this->shop = $shop;
        $this->conditionId = 1;

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
            $this->setDefaultTax();
        }
    }

    // ─── Modal events ────────────────────────────────────────

    #[On('product-created')]
    public function onProductCreated(int $id): void
    {
        $this->selectProduct($id);
    }

    #[On('device-created')]
    public function onDeviceCreated(int $id, string $fullName): void
    {
        // Add to new variant device list if creating a variant
        if ($this->showCreateVariant) {
            if (!collect($this->newVariantDevices)->contains('id', $id)) {
                $this->newVariantDevices[] = ['id' => $id, 'full_name' => $fullName];
            }
        }
    }

    // ─── Steps ───────────────────────────────────────────────

    public function updatedPurchaseType(): void
    {
        $this->setDefaultTax();
        $this->conditionId = 1;
        $this->step = 2;
    }

    public function nextStep(): void
    {
        if ($this->step === 2) {
            $this->validate(
                ['contactId' => 'required'],
                ['contactId.required' => 'Wybierz dostawcę.']
            );
            $this->saveDraftHeader();
        }
        $this->step++;
    }

    public function backToHeader(): void
    {
        $this->step = 2;
    }

    public function restart(): void
    {
        $this->deleteDraft();
        $this->reset([
            'purchaseId', 'purchaseType', 'step',
            'contactId', 'contactName', 'contactSearch',
            'invoiceNumber', 'paymentMethod',
            'productId', 'productName', 'productSearch', 'isDevice',
            'filterBrand', 'filterCategory',
            'selectedVariantId', 'productVariants', 'showCreateVariant',
            'variantDeviceSearch', 'newVariantDevices',
            'priceNet', 'priceSell', 'taxId', 'colorId', 'conditionId', 'quantity',
            'featureImei', 'featureStorage', 'featureBox', 'featureProofOfPurchase', 'featureComment',
        ]);
        $this->resetValidation();
    }

    // ─── Contact ─────────────────────────────────────────────

    #[Computed]
    public function contactResults()
    {
        if (strlen($this->contactSearch) < 2) return collect();

        return Contact::where('name', 'like', "%{$this->contactSearch}%")
            ->orWhere('identity_number', 'like', "%{$this->contactSearch}%")
            ->orderBy('name')
            ->limit(15)
            ->get();
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

    public function clearContact(): void
    {
        $this->contactId = null;
        $this->contactName = null;
    }

    // ─── Product search ──────────────────────────────────────

    #[Computed]
    public function productResults()
    {
        if (strlen($this->productSearch) < 2 && !$this->filterBrand && !$this->filterCategory) {
            return collect();
        }

        $query = Product::current()
            ->with(['brand', 'category'])
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select('products.*');

        if (strlen($this->productSearch) >= 2) {
            $search = $this->productSearch;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(COALESCE(brands.name, ''), ' ', products.name) LIKE ?", ["%{$search}%"])
                  ->orWhere('products.name', 'like', "%{$search}%")
                  ->orWhere('brands.name', 'like', "%{$search}%");
            });
        }

        if ($this->filterBrand !== '') {
            $query->where('products.brand_id', (int) $this->filterBrand);
        }

        if ($this->filterCategory !== '') {
            $query->where('products.parent_category_id', (int) $this->filterCategory);
        }

        return $query->orderBy('brands.name')->orderBy('products.name')->limit(30)->get();
    }

    public function selectProduct(int $id): void
    {
        $product = Product::with('brand')->find($id);
        if (!$product) return;

        $this->productId = $product->id;
        $this->productName = $product->display_name;

        $categoryId = $product->parent_category_id;
        $this->isDevice = $categoryId === 2
            || \App\Models\Category::where('id', $categoryId)->where('parent_category_id', 2)->exists();

        $this->productSearch = '';
        $this->filterBrand = '';
        $this->filterCategory = '';
        $this->selectedVariantId = '';
        $this->showCreateVariant = false;
        $this->newVariantDevices = [];
        $this->variantDeviceSearch = '';
        $this->refreshVariants();
    }

    public function clearProduct(): void
    {
        $this->productId = null;
        $this->productName = null;
        $this->isDevice = false;
        $this->featureImei = '';
        $this->featureStorage = null;
        $this->selectedVariantId = '';
        $this->productVariants = [];
        $this->showCreateVariant = false;
        $this->newVariantDevices = [];
        $this->variantDeviceSearch = '';
    }

    // ─── Variants ────────────────────────────────────────────

    #[Computed]
    public function variantDeviceResults()
    {
        if (strlen($this->variantDeviceSearch) < 2) return collect();

        $search = $this->variantDeviceSearch;
        $selectedIds = collect($this->newVariantDevices)->pluck('id')->all();

        return Device::with('brand')
            ->leftJoin('brands', 'devices.brand_id', '=', 'brands.id')
            ->whereNotIn('devices.id', $selectedIds)
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

    public function addDeviceToNewVariant(int $deviceId): void
    {
        $device = Device::with('brand')->find($deviceId);
        if (!$device) return;
        if (collect($this->newVariantDevices)->contains('id', $deviceId)) return;

        $this->newVariantDevices[] = ['id' => $device->id, 'full_name' => $device->full_name];
        $this->variantDeviceSearch = '';
    }

    public function removeDeviceFromNewVariant(int $deviceId): void
    {
        $this->newVariantDevices = collect($this->newVariantDevices)
            ->reject(fn ($d) => $d['id'] === $deviceId)
            ->values()
            ->all();
    }

    public function saveVariant(): void
    {
        if (!$this->productId) return;

        if (empty($this->newVariantDevices)) {
            $this->addError('variant', 'Dodaj przynajmniej jedno urządzenie.');
            return;
        }

        $variant = ProductVariant::create([
            'product_id' => $this->productId,
            'created_by' => auth()->id(),
        ]);

        $deviceIds = collect($this->newVariantDevices)->pluck('id')->all();
        $variant->devices()->sync($deviceIds);

        $this->refreshVariants();
        $this->selectedVariantId = (string) $variant->id;
        $this->showCreateVariant = false;
        $this->newVariantDevices = [];
        $this->variantDeviceSearch = '';
    }

    public function cancelCreateVariant(): void
    {
        $this->showCreateVariant = false;
        $this->newVariantDevices = [];
        $this->variantDeviceSearch = '';
    }

    private function refreshVariants(): void
    {
        if (!$this->productId) {
            $this->productVariants = [];
            return;
        }

        $variants = ProductVariant::where('product_id', $this->productId)
            ->with('devices.brand')
            ->get();

        $this->productVariants = $variants->map(fn ($v) => [
            'id' => $v->id,
            'display_name' => $v->display_name,
        ])->all();

        // Auto-select if only one variant
        if (count($this->productVariants) === 1) {
            $this->selectedVariantId = (string) $this->productVariants[0]['id'];
        }
    }

    // ─── Lookups ─────────────────────────────────────────────

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
    public function paymentMethods()
    {
        return PaymentMethod::cases();
    }

    #[Computed]
    public function draftItems()
    {
        if (!$this->purchaseId) return collect();

        return PurchasedItem::where('purchase_id', $this->purchaseId)
            ->with(['item.product.brand', 'item.variant.devices', 'item.color', 'item.condition', 'tax'])
            ->get();
    }

    // ─── Items ───────────────────────────────────────────────

    public function addItem(): void
    {
        $this->validate([
            'quantity' => 'required|integer|min:1|max:200',
            'productId' => 'required|exists:products,id',
            'conditionId' => 'required|exists:conditions,id',
            'priceNet' => 'required|numeric|gt:0',
            'taxId' => 'required|integer|exists:taxes,id',
            'featureImei' => $this->isDevice && $this->featureImei !== ''
                ? 'string|unique:items,feature_imei'
                : 'nullable',
            'selectedVariantId' => [
                'required',
                Rule::exists('product_variants', 'id')->where('product_id', $this->productId),
            ],
        ], [
            'quantity.min' => 'Ilość musi wynosić co najmniej 1.',
            'productId.required' => 'Wybierz produkt.',
            'conditionId.required' => 'Wybierz stan.',
            'priceNet.required' => 'Wpisz cenę netto.',
            'priceNet.gt' => 'Cena musi być większa od zera.',
            'taxId.required' => 'Wybierz stawkę VAT.',
            'featureImei.unique' => 'Ten IMEI już istnieje w systemie.',
            'selectedVariantId.required' => 'Wybierz wariant.',
        ]);

        $this->saveDraftHeader();

        $priceGrosze = (int) round((float) $this->priceNet * 100);
        $qty = $this->isDevice ? 1 : $this->quantity;
        $variantId = (int) $this->selectedVariantId;

        DB::transaction(function () use ($priceGrosze, $qty, $variantId) {
            for ($i = 0; $i < $qty; $i++) {
                $item = Item::create([
                    'parent_shop_id' => $this->shop->id,
                    'product_id' => $this->productId,
                    'variant_id' => $variantId,
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

    // ─── Confirm / Cancel ────────────────────────────────────

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
            $purchase->update(['is_draft' => false]);

            $itemIds = $purchase->purchasedItems()->pluck('item_id');
            Item::whereIn('id', $itemIds)
                ->where('status', ItemStatus::Draft)
                ->update([
                    'status' => ItemStatus::Store,
                    'displaced_at' => now(),
                ]);
        });

        session()->flash('toast', [
            'duration' => false,
            'heading' => 'Zakup potwierdzony',
            'text' => "Zakup #{$purchase->id} — dodano {$purchase->purchasedItems()->count()} przedmiotów",
            'variant' => 'success',
        ]);

        $this->redirectRoute('shop.purchases.index', ['shop' => $this->shop], navigate: true);
    }

    public function cancelDraft(): void
    {
        $this->deleteDraft();

        session()->flash('toast', [
            'duration' => false,
            'heading' => 'Wersja robocza usunięta',
            'variant' => 'warning',
        ]);

        $this->redirectRoute('shop.purchases.index', ['shop' => $this->shop], navigate: true);
    }

    // ─── Private helpers ─────────────────────────────────────

    private function setDefaultTax(): void
    {
        $this->taxId = match ($this->purchaseType) {
            2 => Tax::where('percentage', 0)->first()?->id,
            default => Tax::where('percentage', 23)->first()?->id,
        };
    }

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

    private function deleteDraft(): void
    {
        if (!$this->purchaseId) return;

        $purchase = Purchase::where('id', $this->purchaseId)->where('is_draft', true)->first();
        if (!$purchase) return;

        DB::transaction(function () use ($purchase) {
            $itemIds = $purchase->purchasedItems()->pluck('item_id');
            $purchase->purchasedItems()->delete();
            Item::whereIn('id', $itemIds)->where('status', ItemStatus::Draft)->delete();
            $purchase->delete();
        });
    }

    private function resetItemForm(): void
    {
        $this->priceNet = '';
        $this->priceSell = '';
        $this->quantity = 1;
        $this->featureImei = '';
        $this->colorId = null;
        $this->featureStorage = null;
        $this->featureBox = false;
        $this->featureProofOfPurchase = false;
        $this->featureComment = '';
    }

    public function updatedPaymentMethod(): void
    {
        $this->saveDraftHeader();
    }

    public function updatedInvoiceNumber(): void
    {
        $this->saveDraftHeader();
    }

    public function render()
    {
        return view('livewire.purchases.create')
            ->title('Nowy zakup');
    }
}