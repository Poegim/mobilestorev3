<?php

namespace App\Livewire\Concerns;

use App\Models\Category;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

trait WithCategoryFilter
{
    #[Url]
    public string $category = '';

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    #[Computed(persist: true)]
    public function categoryTree(): array
    {
        $all = Category::orderBy('name')->get(['id', 'name', 'parent_category_id']);
        $tree = [];

        // Top-level nodes sit under the root "Przedmioty" (id = 1)
        foreach ($all->where('parent_category_id', 1) as $parent) {
            $tree[] = [
                'id' => $parent->id,
                'name' => $parent->name,
                'children' => $this->childrenRecursive($all, $parent->id),
            ];
        }

        return $tree;
    }

    private function childrenRecursive(Collection $all, int $parentId, int $depth = 0): array
    {
        $result = [];

        foreach ($all->where('parent_category_id', $parentId)->sortBy('name') as $child) {
            $result[] = ['id' => $child->id, 'name' => $child->name, 'depth' => $depth];
            $result = array_merge($result, $this->childrenRecursive($all, $child->id, $depth + 1));
        }

        return $result;
    }

    /**
     * Given category id plus all descendant ids. Products hang off leaf
     * categories, so filtering by a parent node must include the subtree.
     */
    protected function descendantCategoryIds(int $categoryId): array
    {
        $all = Category::get(['id', 'parent_category_id']);
        $ids = [$categoryId];
        $stack = [$categoryId];

        while ($stack) {
            $parent = array_pop($stack);
            foreach ($all->where('parent_category_id', $parent) as $child) {
                $ids[] = (int) $child->id;
                $stack[] = (int) $child->id;
            }
        }

        return $ids;
    }
}