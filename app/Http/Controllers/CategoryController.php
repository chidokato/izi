<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $types = Category::types();
        $groupedTrees = collect();

        foreach (array_keys($types) as $type) {
            $categories = Category::query()
                ->where('type', $type)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with('childrenTree')
                ->get();

            $groupedTrees->put($type, $categories);
        }

        return view('backend.categories.index', compact('types', 'groupedTrees'));
    }

    public function create(Request $request): View
    {
        $types = Category::types();
        $parentOptions = $this->getParentOptions();

        return view('backend.categories.create', compact('types', 'parentOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(Category::types()))],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
                        $parent = Category::find($value);
                        if ($parent && $parent->type !== $request->input('type')) {
                            $fail('Danh mục cha phải cùng loại với danh mục.');
                        }
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slugInput = $validated['slug'] ?? '';
        $slug = ! empty($slugInput) ? Str::slug($slugInput) : Str::slug($validated['name']);
        $slug = $this->generateUniqueSlug($slug);

        Category::create([
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('backend.categories.index')
            ->with('success', 'Them category thanh cong.');
    }

    public function edit(Category $category): View
    {
        $types = Category::types();
        $parentOptions = $this->getParentOptions($category);

        return view('backend.categories.edit', compact('category', 'types', 'parentOptions'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(Category::types()))],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category, $request) {
                    if ((int) $value === (int) $category->id) {
                        $fail('Danh mục cha không thể là chính nó.');
                    }
                    if ($value && $this->isDescendant($category, (int) $value)) {
                        $fail('Danh mục cha không thể là danh mục con của chính nó.');
                    }
                    if ($value) {
                        $parent = Category::find($value);
                        if ($parent && $parent->type !== $request->input('type')) {
                            $fail('Danh mục cha phải cùng loại với danh mục.');
                        }
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slugInput = $validated['slug'] ?? '';
        $slug = ! empty($slugInput) ? Str::slug($slugInput) : Str::slug($validated['name']);
        $slug = $this->generateUniqueSlug($slug, $category->id);

        $category->update([
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('backend.categories.index')
            ->with('success', 'Cap nhat category thanh cong.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('backend.categories.index')
            ->with('success', 'Xoa category thanh cong.');
    }

    public function toggleStatus(Category $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'is_active' => $category->is_active,
            'label' => $category->is_active ? 'Hien thi' : 'An',
            'message' => 'Cap nhat trang thai thanh cong.',
        ]);
    }

    public function updateSortOrder(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $category->update(['sort_order' => $validated['sort_order']]);

        return response()->json([
            'sort_order' => $category->sort_order,
            'message' => 'Cap nhat thu tu thanh cong.',
        ]);
    }

    protected function getParentOptions(?Category $excludeCategory = null): array
    {
        $options = [];

        foreach (array_keys(Category::types()) as $type) {
            $query = Category::query()
                ->where('type', $type)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with('childrenTree');

            $options[$type] = $this->buildCategoryOptions($query->get(), 0, $excludeCategory);
        }

        return $options;
    }

    protected function buildCategoryOptions($categories, $level = 0, ?Category $excludeCategory = null): array
    {
        $result = [];

        foreach ($categories as $cat) {
            if ($excludeCategory && ((int) $cat->id === (int) $excludeCategory->id || $this->isDescendant($excludeCategory, (int) $cat->id))) {
                continue;
            }

            $prefix = str_repeat('— ', $level);
            $result[$cat->id] = $prefix . $cat->name;

            if ($cat->children_tree && $cat->children_tree->isNotEmpty()) {
                $result += $this->buildCategoryOptions($cat->children_tree, $level + 1, $excludeCategory);
            }
        }

        return $result;
    }

    protected function isDescendant(Category $category, int $targetId): bool
    {
        if (! $category->relationLoaded('childrenTree')) {
            $category->load('childrenTree');
        }

        foreach ($category->children_tree as $child) {
            if ((int) $child->id === $targetId || $this->isDescendant($child, $targetId)) {
                return true;
            }
        }

        return false;
    }

    protected function generateUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $originalSlug = $slug;
        $count = 1;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}
