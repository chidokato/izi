<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        $menuTree = Menu::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with('childrenTree')
            ->get();

        return view('backend.menus.index', compact('menuTree'));
    }

    public function create(): View
    {
        $parentOptions = $this->getParentOptions();

        return view('backend.menus.create', compact('parentOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:menus,slug'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'target' => ['required', Rule::in([Menu::TARGET_SELF, Menu::TARGET_BLANK])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slugInput = $validated['slug'] ?? '';
        $slug = ! empty($slugInput) ? Str::slug($slugInput) : Str::slug($validated['name']);
        $slug = $this->generateUniqueSlug($slug);

        Menu::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'target' => $validated['target'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('backend.menus.index')
            ->with('success', 'Them menu thanh cong.');
    }

    public function edit(Menu $menu): View
    {
        $parentOptions = $this->getParentOptions($menu);

        return view('backend.menus.edit', compact('menu', 'parentOptions'));
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('menus', 'slug')->ignore($menu->id),
            ],
            'parent_id' => [
                'nullable',
                'exists:menus,id',
                function ($attribute, $value, $fail) use ($menu) {
                    if ((int) $value === (int) $menu->id) {
                        $fail('Menu cha không thể là chính nó.');
                    }
                    if ($value && $this->isDescendant($menu, (int) $value)) {
                        $fail('Menu cha không thể là menu con của chính nó.');
                    }
                },
            ],
            'target' => ['required', Rule::in([Menu::TARGET_SELF, Menu::TARGET_BLANK])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slugInput = $validated['slug'] ?? '';
        $slug = ! empty($slugInput) ? Str::slug($slugInput) : Str::slug($validated['name']);
        $slug = $this->generateUniqueSlug($slug, $menu->id);

        $menu->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'target' => $validated['target'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('backend.menus.index')
            ->with('success', 'Cap nhat menu thanh cong.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()
            ->route('backend.menus.index')
            ->with('success', 'Xoa menu thanh cong.');
    }

    public function toggleStatus(Menu $menu): JsonResponse
    {
        $menu->update(['is_active' => ! $menu->is_active]);

        return response()->json([
            'is_active' => $menu->is_active,
            'label' => $menu->is_active ? 'Hien thi' : 'An',
            'message' => 'Cap nhat trang thai thanh cong.',
        ]);
    }

    public function updateSortOrder(Request $request, Menu $menu): JsonResponse
    {
        $validated = $request->validate([
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $menu->update(['sort_order' => $validated['sort_order']]);

        return response()->json([
            'sort_order' => $menu->sort_order,
            'message' => 'Cap nhat thu tu thanh cong.',
        ]);
    }

    protected function getParentOptions(?Menu $excludeMenu = null): array
    {
        $query = Menu::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with('childrenTree');

        return $this->buildMenuOptions($query->get(), 0, $excludeMenu);
    }

    protected function buildMenuOptions($menus, $level = 0, ?Menu $excludeMenu = null): array
    {
        $result = [];

        foreach ($menus as $menu) {
            if ($excludeMenu && ((int) $menu->id === (int) $excludeMenu->id || $this->isDescendant($excludeMenu, (int) $menu->id))) {
                continue;
            }

            $prefix = str_repeat('-- ', $level);
            $result[$menu->id] = $prefix . $menu->name;

            if ($menu->children_tree && $menu->children_tree->isNotEmpty()) {
                $result += $this->buildMenuOptions($menu->children_tree, $level + 1, $excludeMenu);
            }
        }

        return $result;
    }

    protected function isDescendant(Menu $menu, int $targetId): bool
    {
        if (! $menu->relationLoaded('childrenTree')) {
            $menu->load('childrenTree');
        }

        foreach ($menu->children_tree as $child) {
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
            Menu::query()
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
