<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostFloorPlan;
use App\Models\PostImage;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $type = $this->getType($request);
        $typeLabel = $this->getTypeLabel($type);

        $posts = Post::query()
            ->whereIn('type', $type === Post::TYPE_COURSE ? [Post::TYPE_COURSE, Post::TYPE_PRODUCT] : [$type])
            ->with(['category', 'seller', 'province', 'ward'])
            ->latest()
            ->paginate(15);

        return view('backend.contents.index', compact('type', 'typeLabel', 'posts'));
    }

    public function create(Request $request): View
    {
        $type = $this->getType($request);
        $typeLabel = $this->getTypeLabel($type);

        $categories = $this->getCategoryOptions($type);
        $provinceOptions = Province::orderBy('name')->pluck('name', 'id');
        $wardOptions = collect();
        $wardMap = Province::with('wards')->get()->mapWithKeys(function ($province) {
            return [
                $province->id => $province->wards->map(function ($ward) {
                    return [
                        'id' => $ward->id,
                        'name' => $ward->name,
                    ];
                })->values(),
            ];
        })->toArray();
        $sellerOptions = User::orderBy('name')->pluck('name', 'id');

        return view('backend.contents.create', compact(
            'type',
            'typeLabel',
            'categories',
            'provinceOptions',
            'wardOptions',
            'wardMap',
            'sellerOptions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $this->getType($request);
        $typeLabel = $this->getTypeLabel($type);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'what_to_learn' => ['nullable', 'string'],
            'course_includes' => ['nullable', 'string'],
            'course_requirements' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'location_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'pdf_file_input' => ['nullable', 'file', 'mimes:pdf,pptx', 'max:51200'],
            'seller_id' => ['nullable', 'exists:users,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_unit' => ['nullable', Rule::in(['ty', 'trieu'])],
            'sales_policy' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'map_embed' => ['nullable', 'string'],
            'area_from' => ['nullable', 'numeric', 'min:0'],
            'area_to' => ['nullable', 'numeric', 'min:0'],
            'floor_count_from' => ['nullable', 'integer', 'min:0'],
            'floor_count_to' => ['nullable', 'integer', 'min:0'],
            'unit_count_from' => ['nullable', 'integer', 'min:0'],
            'unit_count_to' => ['nullable', 'integer', 'min:0'],
            'bedroom_count_from' => ['nullable', 'integer', 'min:0'],
            'bedroom_count_to' => ['nullable', 'integer', 'min:0'],
            'bathroom_count_from' => ['nullable', 'integer', 'min:0'],
            'bathroom_count_to' => ['nullable', 'integer', 'min:0'],
        ]);

        $slugInput = $request->input('slug');
        $slug = ! empty($slugInput) ? Str::slug($slugInput) : Str::slug($request->input('title'));
        $slug = $this->generateUniqueSlug($slug);

        $price = null;
        if (in_array($type, [Post::TYPE_PRODUCT, Post::TYPE_COURSE]) && $request->filled('price')) {
            if ($type === Post::TYPE_PRODUCT) {
                $unit = $request->input('price_unit', 'ty');
                $multiplier = ($unit === 'ty') ? 1000000000 : 1000000;
                $price = (float) $request->input('price') * $multiplier;
            } else {
                $price = (float) $request->input('price');
            }
        }

        $imagePath = null;
        if ($request->hasFile('image_file')) {
            $imagePath = $this->storeImage($request->file('image_file'), 'posts');
        }

        $locationImagePath = null;
        if ($request->hasFile('location_image_file')) {
            $locationImagePath = $this->storeImage($request->file('location_image_file'), 'posts/location');
        }

        $pdfPath = null;
        if ($request->hasFile('pdf_file_input')) {
            $pdfPath = $this->storeImage($request->file('pdf_file_input'), 'posts/pdf');
        }

        $post = Post::create([
            'type' => $type,
            'category_id' => $request->input('category_id') ?: null,
            'seller_id' => in_array($type, [Post::TYPE_PRODUCT, Post::TYPE_COURSE]) ? ($request->input('seller_id') ?: null) : null,
            'title' => $request->input('title'),
            'slug' => $slug,
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
            'summary' => $request->input('summary'),
            'sales_policy' => ($type === Post::TYPE_PRODUCT) ? $request->input('sales_policy') : null,
            'content' => $request->input('content'),
            'what_to_learn' => $request->input('what_to_learn'),
            'course_includes' => $request->input('course_includes'),
            'course_requirements' => $request->input('course_requirements'),
            'address' => ($type === Post::TYPE_PRODUCT) ? $request->input('address') : null,
            'province_id' => ($type === Post::TYPE_PRODUCT) ? ($request->input('province_id') ?: null) : null,
            'ward_id' => ($type === Post::TYPE_PRODUCT) ? ($request->input('ward_id') ?: null) : null,
            'map_embed' => ($type === Post::TYPE_PRODUCT) ? $request->input('map_embed') : null,
            'location_image' => $locationImagePath,
            'area' => $request->input('area_from'),
            'area_from' => $request->input('area_from'),
            'area_to' => $request->input('area_to'),
            'floor_count' => $request->input('floor_count_from'),
            'floor_count_from' => $request->input('floor_count_from'),
            'floor_count_to' => $request->input('floor_count_to'),
            'unit_count' => $request->input('unit_count_from'),
            'unit_count_from' => $request->input('unit_count_from'),
            'unit_count_to' => $request->input('unit_count_to'),
            'bedroom_count' => $request->input('bedroom_count_from'),
            'bedroom_count_from' => $request->input('bedroom_count_from'),
            'bedroom_count_to' => $request->input('bedroom_count_to'),
            'bathroom_count' => $request->input('bathroom_count_from'),
            'bathroom_count_from' => $request->input('bathroom_count_from'),
            'bathroom_count_to' => $request->input('bathroom_count_to'),
            'image' => $imagePath,
            'pdf_file' => $pdfPath,
            'price' => $price,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => ($type === Post::TYPE_PRODUCT) ? $request->boolean('is_featured', false) : false,
            'published_at' => now(),
        ]);

        if ($type === Post::TYPE_PRODUCT) {
            $this->storeGalleryImages($post, $request);
            $this->storeFloorPlans($post, $request);
        }

        $routePrefix = in_array($type, [Post::TYPE_COURSE, Post::TYPE_PRODUCT]) ? 'backend.courses' : 'backend.news';

        if ($request->boolean('save_stay')) {
            return redirect()
                ->route($routePrefix . '.edit', $post)
                ->with('success', 'Them ' . strtolower($typeLabel) . ' thanh cong.');
        }

        return redirect()
            ->route($routePrefix . '.index')
            ->with('success', 'Them ' . strtolower($typeLabel) . ' thanh cong.');
    }

    public function edit(Request $request, Post $post): View
    {
        $type = $post->type;
        $typeLabel = $this->getTypeLabel($type);

        $categories = $this->getCategoryOptions($type);
        $provinceOptions = Province::orderBy('name')->pluck('name', 'id');
        $wardOptions = $post->province_id
            ? Ward::where('province_id', $post->province_id)->orderBy('name')->pluck('name', 'id')
            : collect();
        $wardMap = Province::with('wards')->get()->mapWithKeys(function ($province) {
            return [
                $province->id => $province->wards->map(function ($ward) {
                    return [
                        'id' => $ward->id,
                        'name' => $ward->name,
                    ];
                })->values(),
            ];
        })->toArray();
        $sellerOptions = User::orderBy('name')->pluck('name', 'id');
        $galleryImages = $post->galleryImages;
        $floorPlans = $post->floorPlans;

        return view('backend.contents.edit', compact(
            'post',
            'type',
            'typeLabel',
            'categories',
            'provinceOptions',
            'wardOptions',
            'wardMap',
            'sellerOptions',
            'galleryImages',
            'floorPlans'
        ));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $type = $post->type;
        $typeLabel = $this->getTypeLabel($type);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'what_to_learn' => ['nullable', 'string'],
            'course_includes' => ['nullable', 'string'],
            'course_requirements' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'location_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'pdf_file_input' => ['nullable', 'file', 'mimes:pdf,pptx', 'max:51200'],
            'seller_id' => ['nullable', 'exists:users,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_unit' => ['nullable', Rule::in(['ty', 'trieu'])],
            'sales_policy' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'map_embed' => ['nullable', 'string'],
            'area_from' => ['nullable', 'numeric', 'min:0'],
            'area_to' => ['nullable', 'numeric', 'min:0'],
            'floor_count_from' => ['nullable', 'integer', 'min:0'],
            'floor_count_to' => ['nullable', 'integer', 'min:0'],
            'unit_count_from' => ['nullable', 'integer', 'min:0'],
            'unit_count_to' => ['nullable', 'integer', 'min:0'],
            'bedroom_count_from' => ['nullable', 'integer', 'min:0'],
            'bedroom_count_to' => ['nullable', 'integer', 'min:0'],
            'bathroom_count_from' => ['nullable', 'integer', 'min:0'],
            'bathroom_count_to' => ['nullable', 'integer', 'min:0'],
        ]);

        $slugInput = $request->input('slug');
        $slug = ! empty($slugInput) ? Str::slug($slugInput) : Str::slug($request->input('title'));
        $slug = $this->generateUniqueSlug($slug, $post->id);

        $price = null;
        if (in_array($type, [Post::TYPE_PRODUCT, Post::TYPE_COURSE]) && $request->filled('price')) {
            if ($type === Post::TYPE_PRODUCT) {
                $unit = $request->input('price_unit', 'ty');
                $multiplier = ($unit === 'ty') ? 1000000000 : 1000000;
                $price = (float) $request->input('price') * $multiplier;
            } else {
                $price = (float) $request->input('price');
            }
        }

        $imagePath = $post->image;
        if ($request->boolean('remove_image')) {
            $this->deleteImageIfExists($imagePath);
            $imagePath = null;
        }
        if ($request->hasFile('image_file')) {
            $this->deleteImageIfExists($imagePath);
            $imagePath = $this->storeImage($request->file('image_file'), 'posts');
        }

        $locationImagePath = $post->location_image;
        if ($request->boolean('remove_location_image')) {
            $this->deleteImageIfExists($locationImagePath);
            $locationImagePath = null;
        }
        if ($request->hasFile('location_image_file')) {
            $this->deleteImageIfExists($locationImagePath);
            $locationImagePath = $this->storeImage($request->file('location_image_file'), 'posts/location');
        }

        $pdfPath = $post->pdf_file;
        if ($request->boolean('remove_pdf_file')) {
            $this->deleteImageIfExists($pdfPath);
            $pdfPath = null;
        }
        if ($request->hasFile('pdf_file_input')) {
            $this->deleteImageIfExists($pdfPath);
            $pdfPath = $this->storeImage($request->file('pdf_file_input'), 'posts/pdf');
        }

        $post->update([
            'category_id' => $request->input('category_id') ?: null,
            'seller_id' => in_array($type, [Post::TYPE_PRODUCT, Post::TYPE_COURSE]) ? ($request->input('seller_id') ?: null) : null,
            'title' => $request->input('title'),
            'slug' => $slug,
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
            'summary' => $request->input('summary'),
            'sales_policy' => ($type === Post::TYPE_PRODUCT) ? $request->input('sales_policy') : null,
            'content' => $request->input('content'),
            'what_to_learn' => $request->input('what_to_learn'),
            'course_includes' => $request->input('course_includes'),
            'course_requirements' => $request->input('course_requirements'),
            'address' => ($type === Post::TYPE_PRODUCT) ? $request->input('address') : null,
            'province_id' => ($type === Post::TYPE_PRODUCT) ? ($request->input('province_id') ?: null) : null,
            'ward_id' => ($type === Post::TYPE_PRODUCT) ? ($request->input('ward_id') ?: null) : null,
            'map_embed' => ($type === Post::TYPE_PRODUCT) ? $request->input('map_embed') : null,
            'location_image' => $locationImagePath,
            'area' => $request->input('area_from'),
            'area_from' => $request->input('area_from'),
            'area_to' => $request->input('area_to'),
            'floor_count' => $request->input('floor_count_from'),
            'floor_count_from' => $request->input('floor_count_from'),
            'floor_count_to' => $request->input('floor_count_to'),
            'unit_count' => $request->input('unit_count_from'),
            'unit_count_from' => $request->input('unit_count_from'),
            'unit_count_to' => $request->input('unit_count_to'),
            'bedroom_count' => $request->input('bedroom_count_from'),
            'bedroom_count_from' => $request->input('bedroom_count_from'),
            'bedroom_count_to' => $request->input('bedroom_count_to'),
            'bathroom_count' => $request->input('bathroom_count_from'),
            'bathroom_count_from' => $request->input('bathroom_count_from'),
            'bathroom_count_to' => $request->input('bathroom_count_to'),
            'image' => $imagePath,
            'pdf_file' => $pdfPath,
            'price' => $price,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => ($type === Post::TYPE_PRODUCT) ? $request->boolean('is_featured', false) : false,
        ]);

        if ($type === Post::TYPE_PRODUCT) {
            if ($request->filled('remove_gallery_images')) {
                $removeIds = (array) $request->input('remove_gallery_images');
                $imagesToRemove = $post->galleryImages()->whereIn('id', $removeIds)->get();
                foreach ($imagesToRemove as $img) {
                    $this->deleteImageIfExists($img->image);
                    $img->delete();
                }
            }

            $this->storeGalleryImages($post, $request);

            if ($request->filled('remove_floor_plans')) {
                $removeIds = (array) $request->input('remove_floor_plans');
                $fpsToRemove = $post->floorPlans()->whereIn('id', $removeIds)->get();
                foreach ($fpsToRemove as $fp) {
                    $this->deleteImageIfExists($fp->image);
                    $fp->delete();
                }
            }

            $this->storeFloorPlans($post, $request);
        }

        $routePrefix = in_array($type, [Post::TYPE_COURSE, Post::TYPE_PRODUCT]) ? 'backend.courses' : 'backend.news';

        if ($request->boolean('save_stay')) {
            return redirect()
                ->route($routePrefix . '.edit', $post)
                ->with('success', 'Cap nhat ' . strtolower($typeLabel) . ' thanh cong.');
        }

        return redirect()
            ->route($routePrefix . '.index')
            ->with('success', 'Cap nhat ' . strtolower($typeLabel) . ' thanh cong.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $type = $post->type;
        $typeLabel = $this->getTypeLabel($type);

        $this->deleteImageIfExists($post->image);
        $this->deleteImageIfExists($post->location_image);

        foreach ($post->galleryImages as $img) {
            $this->deleteImageIfExists($img->image);
            $img->delete();
        }

        foreach ($post->floorPlans as $fp) {
            $this->deleteImageIfExists($fp->image);
            $fp->delete();
        }

        $post->delete();

        $routePrefix = in_array($type, [Post::TYPE_COURSE, Post::TYPE_PRODUCT]) ? 'backend.courses' : 'backend.news';

        return redirect()
            ->route($routePrefix . '.index')
            ->with('success', 'Xoa ' . strtolower($typeLabel) . ' thanh cong.');
    }

    public function toggleStatus(Post $post): JsonResponse
    {
        $post->update(['is_active' => ! $post->is_active]);

        return response()->json([
            'is_active' => $post->is_active,
            'label' => $post->is_active ? 'Hien thi' : 'An',
            'message' => 'Cap nhat trang thai thanh cong.',
        ]);
    }

    public function toggleFeatured(Post $post): JsonResponse
    {
        $post->update(['is_featured' => ! $post->is_featured]);

        return response()->json([
            'is_featured' => $post->is_featured,
            'label' => $post->is_featured ? 'Bat' : 'Tat',
            'message' => 'Cap nhat du an noi bat thanh cong.',
        ]);
    }

    protected function getType(Request $request): string
    {
        if ($request->routeIs('backend.courses.*') || $request->routeIs('backend.products.*') || in_array($request->route('type'), ['course', 'product'])) {
            return Post::TYPE_COURSE;
        }

        return Post::TYPE_NEWS;
    }

    protected function getTypeLabel(string $type): string
    {
        return in_array($type, [Post::TYPE_COURSE, Post::TYPE_PRODUCT]) ? 'Course' : 'News';
    }

    protected function getCategoryOptions(string $type): array
    {
        $categories = Category::query()
            ->where('type', $type)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with('childrenTree')
            ->get();

        return $this->buildCategoryOptions($categories);
    }

    protected function buildCategoryOptions($categories, int $level = 0): array
    {
        $result = [];
        foreach ($categories as $cat) {
            $prefix = str_repeat('— ', $level);
            $result[$cat->id] = $prefix . $cat->name;
            if ($cat->children_tree && $cat->children_tree->isNotEmpty()) {
                $result += $this->buildCategoryOptions($cat->children_tree, $level + 1);
            }
        }

        return $result;
    }

    protected function generateUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        if (empty($slug)) {
            $slug = 'post-' . Str::random(6);
        }

        $original = $slug;
        $count = 1;

        while (
            Post::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }

    protected function storeImage($file, string $folder = 'posts'): string
    {
        $directory = public_path('uploads/' . $folder);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = str_replace('/', '-', $folder) . '-' . Str::random(20) . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($directory, $filename);

        return 'uploads/' . $folder . '/' . $filename;
    }

    protected function deleteImageIfExists(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, 'uploads/')) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    protected function storeGalleryImages(Post $post, Request $request): void
    {
        $types = [
            'interior' => PostImage::TYPE_INTERIOR,
            'perspective' => PostImage::TYPE_PERSPECTIVE,
            'amenity' => PostImage::TYPE_AMENITY,
        ];

        foreach ($types as $field => $typeKey) {
            $inputName = "gallery_files_{$field}";
            if ($request->hasFile($inputName)) {
                $files = $request->file($inputName);
                if (is_array($files)) {
                    $maxSort = $post->galleryImages()->where('image_type', $typeKey)->max('sort_order') ?? 0;
                    foreach ($files as $file) {
                        $path = $this->storeImage($file, 'posts/gallery');
                        $maxSort++;
                        $post->galleryImages()->create([
                            'image' => $path,
                            'image_type' => $typeKey,
                            'sort_order' => $maxSort,
                        ]);
                    }
                }
            }
        }
    }

    protected function storeFloorPlans(Post $post, Request $request): void
    {
        $floorPlansInput = $request->input('floor_plans', []);
        if (! is_array($floorPlansInput)) {
            return;
        }

        $maxSort = $post->floorPlans()->max('sort_order') ?? 0;
        foreach ($floorPlansInput as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = $item['id'] ?? null;
            $name = $item['name'] ?? null;
            $existingImage = $item['existing_image'] ?? null;
            $imageFile = $request->file("floor_plans.{$index}.image_file");

            if (! $id && ! $name && ! $existingImage && ! $imageFile) {
                continue;
            }

            if ($id) {
                $fp = $post->floorPlans()->find($id);
                if ($fp) {
                    $imagePath = $fp->image;
                    if ($imageFile) {
                        $this->deleteImageIfExists($imagePath);
                        $imagePath = $this->storeImage($imageFile, 'posts/floor-plans');
                    }
                    $fp->update([
                        'name' => $name,
                        'image' => $imagePath,
                    ]);
                }
            } else {
                $imagePath = null;
                if ($imageFile) {
                    $imagePath = $this->storeImage($imageFile, 'posts/floor-plans');
                }
                if ($name || $imagePath) {
                    $maxSort++;
                    $post->floorPlans()->create([
                        'name' => $name,
                        'image' => $imagePath,
                        'sort_order' => $maxSort,
                    ]);
                }
            }
        }
    }
}
