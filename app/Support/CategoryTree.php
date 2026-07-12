<?php

namespace App\Support;

use App\Models\Category;

class CategoryTree
{
    /** @var array<int, int[]>|null  parent_category_id => direct child ids */
    private ?array $childrenByParent = null;

    /** @var array<int, int[]> memoized descendant lookups */
    private array $descendantsCache = [];

    /**
     * All descendant category IDs, including the root itself.
     *
     * @return int[]
     */
    public function descendantIds(int $rootId): array
    {
        if (isset($this->descendantsCache[$rootId])) {
            return $this->descendantsCache[$rootId];
        }

        $this->loadTree();

        $ids   = [];
        $stack = [$rootId];

        while ($stack) {
            $id    = array_pop($stack);
            $ids[] = $id;

            foreach ($this->childrenByParent[$id] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return $this->descendantsCache[$rootId] = $ids;
    }

    /** Load the whole category tree in a single query. */
    private function loadTree(): void
    {
        if ($this->childrenByParent !== null) {
            return;
        }

        $this->childrenByParent = Category::query()
            ->select('id', 'parent_category_id')
            ->get()
            ->groupBy('parent_category_id')
            ->map(fn ($rows) => $rows->pluck('id')->all())
            ->all();
    }
}