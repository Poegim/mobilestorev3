<?php

namespace App\Livewire;

use App\Enums\DashboardPeriod;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\Shop;
use App\Reports\Dashboard\RevenueReport;
use App\Reports\Dashboard\ShopStatsReport;
use App\Support\CategoryTree;
use App\Support\Dashboard\DashboardScope;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Reports\Dashboard\AccessoryMarginReport;
use Illuminate\Support\Collection;

class Dashboard extends Component
{
    public ?Shop $shop = null;
    
    /** Accessory margins per shop id — loaded once, independent of the period. */
    public array $shopAccessoryMargins = [];

    public DashboardPeriod $period = DashboardPeriod::Today;

    // Lazy-load flags
    public bool $revenueLoaded     = false;
    public bool $shopStatsLoaded   = false;
    public bool $topProductsLoaded = false;

    // Lazy-loaded data (persisted in component state)
    public int $todayRevenue = 0;
    public int $todaySells   = 0;
    public int $monthRevenue = 0;
    public int $monthSells   = 0;

    public array $todayCategories = [];
    public array $monthCategories = [];
    public array $shopStats       = [];

    /** Per-request scope memo (not persisted across requests). */
    protected ?DashboardScope $scopeCache = null;

    #[Computed]
    public function periodLabel(): string
    {
        return $this->period->label();
    }
    

    private function dashboardShops(): Collection
    {
        if ($this->shop) {
            return collect([$this->shop]);
        }

        $user = auth()->user();

        $query = $user->isAdmin()
            ? Shop::where('archive', false)
            : $user->shops()->where('archive', false);

        return $query->orderBy('order')->orderBy('id')->get();
    }

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;

        // Cheap today sell-count shown before the revenue module loads.
        [$from, $to] = DashboardPeriod::Today->range();
        $this->todaySells = $this->scope()->apply(
            Sell::where('valid', 1)->whereBetween('created_at', [$from, $to])
        )->count();

        // Auto-load default modules on first render.
        if ($this->isAdmin()) {
            $this->loadRevenueStats();
        }
        $this->loadShopStats();
    }

    public function loadRevenueStats(): void
    {
        $report = new RevenueReport($this->scope(), app(CategoryTree::class), $this->isAdmin());

        $period = $report->forPeriod($this->period);
        $this->todayRevenue    = $period['revenue'];
        $this->todaySells      = $period['sells'];
        $this->todayCategories = $period['categories'];

        $month = $report->currentMonth();
        $this->monthRevenue    = $month['revenue'];
        $this->monthSells      = $month['sells'];
        $this->monthCategories = $month['categories'];

        $this->revenueLoaded = true;
    }

    public function loadShopStats(): void
    {
        // Margins depend only on calendar month, not the period — compute once.
        if ($this->shopAccessoryMargins === []) {
            $this->shopAccessoryMargins = app(AccessoryMarginReport::class)
                ->forShops($this->dashboardShops());
        }

        $report = new ShopStatsReport($this->scope(), app(CategoryTree::class));

        $this->shopStats = $report->forPeriod(
            $this->period,
            $this->shop,
            auth()->user(),
            $this->shopAccessoryMargins,
        );
        $this->shopStatsLoaded = true;
    }

    

    public function loadTopProducts(): void
    {
        $this->topProductsLoaded = true;
    }

    public function updatedPeriod(): void
    {
        $reloadRevenue = $this->revenueLoaded;
        $reloadShop    = $this->shopStatsLoaded;

        $this->revenueLoaded   = false;
        $this->shopStatsLoaded = false;

        if ($reloadRevenue) $this->loadRevenueStats();
        if ($reloadShop)    $this->loadShopStats();
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'itemsInStock'   => $this->scope()->apply(Item::where('status', ItemStatus::Store))->count(),
            'todayPurchases' => $this->scope()->apply(
                Purchase::where('valid', 1)->whereBetween('created_at', [
                    Carbon::today()->startOfDay(),
                    Carbon::today()->endOfDay(),
                ])
            )->count(),
            'isAdmin'        => $this->isAdmin(),
            'period'         => $this->period->value, // keep the view's string contract intact
        ]);
    }

    private function scope(): DashboardScope
    {
        return $this->scopeCache ??= DashboardScope::make($this->shop, auth()->user());
    }

    private function isAdmin(): bool
    {
        return auth()->user()->isAdmin();
    }
}