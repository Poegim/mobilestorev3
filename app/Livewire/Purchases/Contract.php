<?php

namespace App\Livewire\Purchases;

use App\Enums\PrintableDocumentType;
use App\Livewire\Concerns\DeniesAccess;
use App\Models\Purchase;
use App\Models\Shop;
use App\Services\PrintableDocumentService;
use Livewire\Component;

class Contract extends Component
{
    use DeniesAccess;

    public Purchase $purchase;

    public ?Shop $shop = null;

    public string $html = '';

    public function mount(Purchase $purchase, ?Shop $shop = null): void
    {
        $user = auth()->user();
        $this->denyUnless(
            $user->isAdmin() || $user->shops()->whereKey($purchase->parent_shop_id)->exists(),
            'Nie masz dostępu do zakupów tego sklepu.'
        );

        $this->purchase = $purchase->load([
            'shop', 'contact', 'purchasedItems.item.product.brand', 'purchasedItems.item.condition',
        ]);
        $this->shop = $shop;

        $this->html = app(PrintableDocumentService::class)->getHtml($this->purchase, PrintableDocumentType::Contract);
    }

    public function save(string $html): void
    {
        app(PrintableDocumentService::class)->save($this->purchase, PrintableDocumentType::Contract, $html);

        session()->flash('toast', [
            'heading' => 'Zapisano',
            'text'    => 'Zmiany w umowie zostały zapisane.',
            'variant' => 'success',
        ]);
    }

    public function regenerate(): void
    {
        $this->html = app(PrintableDocumentService::class)->regenerate($this->purchase, PrintableDocumentType::Contract);
    }

    public function render()
    {
        return view('livewire.purchases.contract')
            ->title($this->purchase->contractReference());
    }
}