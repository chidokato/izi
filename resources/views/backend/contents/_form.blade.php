@csrf

@php
    $currentImage = old('existing_image', $post->image ?? '');
    $imagePreview = $currentImage ? asset($currentImage) : '';
    $currentLocationImage = old('existing_location_image', $post->location_image ?? '');
    $locationImagePreview = $currentLocationImage ? asset($currentLocationImage) : '';
    $existingGalleryImages = $galleryImages ?? collect();
    $existingFloorPlans = $floorPlans ?? collect();
    $galleryTypes = \App\Models\PostImage::types();
    $galleryImagesByType = collect($galleryTypes)
        ->mapWithKeys(function ($label, $typeKey) use ($existingGalleryImages) {
            return [
                $typeKey => $existingGalleryImages
                    ->filter(fn ($image) => ($image->image_type ?? \App\Models\PostImage::TYPE_PERSPECTIVE) === $typeKey)
                    ->values(),
            ];
        });
    $selectedProvinceId = (string) old('province_id', $post->province_id ?? '');
    $selectedWardId = (string) old('ward_id', $post->ward_id ?? '');
    $provinceOptions = $provinceOptions ?? collect();
    $wardOptions = $wardOptions ?? collect();
    $wardMap = $wardMap ?? [];
    $isCourse = in_array($type, ['course', 'product']);

    $oldFloorPlans = old('floor_plans');
    if ($oldFloorPlans !== null) {
        $floorPlanItems = collect($oldFloorPlans)->values();
    } else {
        $floorPlanItems = $existingFloorPlans
            ->map(fn ($floorPlan) => [
                'id' => $floorPlan->id,
                'name' => $floorPlan->name,
                'existing_image' => $floorPlan->image,
            ])
            ->values();
    }
@endphp

<div
    class="row backend-content-form"
    id="backend-content-form"
    data-ward-map='@json($wardMap)'
    data-url-base="{{ url('/') }}"
    data-slug-prefix="{{ $isCourse ? 'courses' : 'tin-tuc' }}"
>
    <div class="col-xl-9">
        <div class="card border">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">{{ $isCourse ? 'Course Title' : 'Tieu de' }}</label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $post->title ?? '') }}">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $post->slug ?? '') }}">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if (! $isCourse)
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="summary" class="form-label">{{ $isCourse ? 'Short Description / Overview' : 'Mo ta ngan' }}</label>
                            <textarea id="summary" name="summary" rows="3" class="form-control @error('summary') is-invalid @enderror">{{ old('summary', $post->summary ?? '') }}</textarea>
                            @error('summary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @endif



                    @if (! $isCourse && $type === 'product')
                        <div class="col-12">
                            <div class="row mb-3">
                                <div class="col-lg-5">
                                    <div class="mb-3 mb-lg-0">
                                        <label for="address" class="form-label">Dia chi</label>
                                        <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $post->address ?? '') }}">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="mb-3 mb-lg-0">
                                        <label for="province_id" class="form-label">Tinh thanh</label>
                                        <select id="province_id" name="province_id" class="form-select @error('province_id') is-invalid @enderror">
                                            <option value="">Chon tinh thanh</option>
                                            @foreach ($provinceOptions as $provinceId => $provinceName)
                                                <option value="{{ $provinceId }}" {{ $selectedProvinceId === (string) $provinceId ? 'selected' : '' }}>{{ $provinceName }}</option>
                                            @endforeach
                                        </select>
                                        @error('province_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3 mb-lg-0">
                                        <label for="ward_id" class="form-label">Phuong xa</label>
                                        <select id="ward_id" name="ward_id" class="form-select @error('ward_id') is-invalid @enderror">
                                            <option value="">Chon phuong xa</option>
                                            @foreach ($wardOptions as $wardId => $wardName)
                                                <option value="{{ $wardId }}" {{ $selectedWardId === (string) $wardId ? 'selected' : '' }}>{{ $wardName }}</option>
                                            @endforeach
                                        </select>
                                        @error('ward_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="row mb-3 align-items-stretch">
                                <div class="col-lg-9">
                                    <div class="mb-3 mb-lg-0">
                                        <label for="map_embed" class="form-label">Maps</label>
                                        <textarea id="map_embed" name="map_embed" rows="8" class="form-control @error('map_embed') is-invalid @enderror" placeholder="Dan ma nhung Google Maps vao day">{{ old('map_embed', $post->map_embed ?? '') }}</textarea>
                                        @error('map_embed')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="mb-3 mb-lg-0">
                                        <label class="form-label">Anh vi tri</label>
                                        <input type="hidden" name="existing_location_image" value="{{ $currentLocationImage }}">
                                        <input type="hidden" name="remove_location_image" id="remove_location_image" value="0">
                                        <input type="file" id="location_image_file" name="location_image_file" class="d-none" accept="image/*">
                                        <div class="d-flex flex-column gap-2">
                                            <button type="button" id="location-image-preview-box" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden p-0 w-100 content-image-dropzone content-image-dropzone--location">
                                                <img id="location-image-preview" src="{{ $locationImagePreview }}" alt="Location preview" class="w-100 h-100 object-fit-cover {{ $locationImagePreview ? '' : 'd-none' }}">
                                                <div id="location-image-placeholder" class="text-center text-muted px-3 {{ $locationImagePreview ? 'd-none' : '' }}">
                                                    <div class="display-6 mb-2"><i class="ri-image-line"></i></div>
                                                    <div class="fw-semibold">NO IMAGE</div>
                                                    <div>Click hoac drop anh vao day</div>
                                                </div>
                                            </button>
                                            <button type="button" id="remove-location-image-trigger" class="btn btn-soft-danger btn-sm align-self-start {{ $locationImagePreview ? '' : 'd-none' }}">Bo anh vi tri</button>
                                        </div>
                                        @error('location_image_file')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Dien tich (m2)</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" step="0.01" min="0" id="area_from" name="area_from" class="form-control @error('area_from') is-invalid @enderror" value="{{ old('area_from', $post->area_from ?? $post->area ?? '') }}" placeholder="Tu">
                                        @error('area_from')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="number" step="0.01" min="0" id="area_to" name="area_to" class="form-control @error('area_to') is-invalid @enderror" value="{{ old('area_to', $post->area_to ?? $post->area ?? '') }}" placeholder="Den">
                                        @error('area_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">So tang</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" min="0" id="floor_count_from" name="floor_count_from" class="form-control @error('floor_count_from') is-invalid @enderror" value="{{ old('floor_count_from', $post->floor_count_from ?? $post->floor_count ?? '') }}" placeholder="Tu">
                                        @error('floor_count_from')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="number" min="0" id="floor_count_to" name="floor_count_to" class="form-control @error('floor_count_to') is-invalid @enderror" value="{{ old('floor_count_to', $post->floor_count_to ?? $post->floor_count ?? '') }}" placeholder="Den">
                                        @error('floor_count_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">So can</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" min="0" id="unit_count_from" name="unit_count_from" class="form-control @error('unit_count_from') is-invalid @enderror" value="{{ old('unit_count_from', $post->unit_count_from ?? $post->unit_count ?? '') }}" placeholder="Tu">
                                        @error('unit_count_from')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="number" min="0" id="unit_count_to" name="unit_count_to" class="form-control @error('unit_count_to') is-invalid @enderror" value="{{ old('unit_count_to', $post->unit_count_to ?? $post->unit_count ?? '') }}" placeholder="Den">
                                        @error('unit_count_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">So phong ngu</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" min="0" id="bedroom_count_from" name="bedroom_count_from" class="form-control @error('bedroom_count_from') is-invalid @enderror" value="{{ old('bedroom_count_from', $post->bedroom_count_from ?? $post->bedroom_count ?? '') }}" placeholder="Tu">
                                        @error('bedroom_count_from')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="number" min="0" id="bedroom_count_to" name="bedroom_count_to" class="form-control @error('bedroom_count_to') is-invalid @enderror" value="{{ old('bedroom_count_to', $post->bedroom_count_to ?? $post->bedroom_count ?? '') }}" placeholder="Den">
                                        @error('bedroom_count_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">So WC</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" min="0" id="bathroom_count_from" name="bathroom_count_from" class="form-control @error('bathroom_count_from') is-invalid @enderror" value="{{ old('bathroom_count_from', $post->bathroom_count_from ?? $post->bathroom_count ?? '') }}" placeholder="Tu">
                                        @error('bathroom_count_from')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="number" min="0" id="bathroom_count_to" name="bathroom_count_to" class="form-control @error('bathroom_count_to') is-invalid @enderror" value="{{ old('bathroom_count_to', $post->bathroom_count_to ?? $post->bathroom_count ?? '') }}" placeholder="Den">
                                        @error('bathroom_count_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($isCourse)
                        <div class="col-12">
                            <div class="mb-0">
                                <ul class="nav nav-tabs" id="courseContentTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-tab-pane" type="button" role="tab" aria-controls="summary-tab-pane" aria-selected="true">Short Description / Overview</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content-tab-pane" type="button" role="tab" aria-controls="content-tab-pane" aria-selected="false">Nội dung khóa học</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="what-to-learn-tab" data-bs-toggle="tab" data-bs-target="#what-to-learn-tab-pane" type="button" role="tab" aria-controls="what-to-learn-tab-pane" aria-selected="false">Bạn sẽ học được gì</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="course-includes-tab" data-bs-toggle="tab" data-bs-target="#course-includes-tab-pane" type="button" role="tab" aria-controls="course-includes-tab-pane" aria-selected="false">Khóa học này bao gồm</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="course-requirements-tab" data-bs-toggle="tab" data-bs-target="#course-requirements-tab-pane" type="button" role="tab" aria-controls="course-requirements-tab-pane" aria-selected="false">Yêu cầu khóa học</button>
                                    </li>
                                </ul>
                                <div class="tab-content border border-top-0 p-3" id="courseContentTabsContent">
                                    <div class="tab-pane fade show active" id="summary-tab-pane" role="tabpanel" aria-labelledby="summary-tab" tabindex="0">
                                        <label for="summary" class="visually-hidden">Short Description / Overview</label>
                                        <textarea id="summary" name="summary" rows="8" class="form-control editor @error('summary') is-invalid @enderror">{{ old('summary', $post->summary ?? '') }}</textarea>
                                        @error('summary')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="tab-pane fade" id="content-tab-pane" role="tabpanel" aria-labelledby="content-tab" tabindex="0">
                                        <textarea id="content" name="content" rows="8" class="form-control editor @error('content') is-invalid @enderror">{{ old('content', $post->content ?? '') }}</textarea>
                                        @error('content')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="tab-pane fade" id="what-to-learn-tab-pane" role="tabpanel" aria-labelledby="what-to-learn-tab" tabindex="0">
                                        <textarea id="what_to_learn" name="what_to_learn" rows="8" class="form-control editor @error('what_to_learn') is-invalid @enderror">{{ old('what_to_learn', $post->what_to_learn ?? '') }}</textarea>
                                        @error('what_to_learn')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="tab-pane fade" id="course-includes-tab-pane" role="tabpanel" aria-labelledby="course-includes-tab" tabindex="0">
                                        <textarea id="course_includes" name="course_includes" rows="8" class="form-control editor @error('course_includes') is-invalid @enderror">{{ old('course_includes', $post->course_includes ?? '') }}</textarea>
                                        @error('course_includes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="tab-pane fade" id="course-requirements-tab-pane" role="tabpanel" aria-labelledby="course-requirements-tab" tabindex="0">
                                        <textarea id="course_requirements" name="course_requirements" rows="8" class="form-control editor @error('course_requirements') is-invalid @enderror">{{ old('course_requirements', $post->course_requirements ?? '') }}</textarea>
                                        @error('course_requirements')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="col-12">
                            <div class="mb-0">
                                <label for="content" class="form-label">Noi dung</label>
                                <textarea id="content" name="content" rows="8" class="form-control editor @error('content') is-invalid @enderror">{{ old('content', $post->content ?? '') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border">
            <div class="card-header">
                <h5 class="card-title mb-0">Cau hinh SEO</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="seo_title" class="form-label">Title</label>
                            <input type="text" id="seo_title" name="seo_title" class="form-control @error('seo_title') is-invalid @enderror" value="{{ old('seo_title', $post->seo_title ?? '') }}" placeholder="Nhap SEO title">
                            @error('seo_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="seo_description" class="form-label">Description</label>
                            <textarea id="seo_description" name="seo_description" rows="3" class="form-control @error('seo_description') is-invalid @enderror" placeholder="Nhap SEO description">{{ old('seo_description', $post->seo_description ?? '') }}</textarea>
                            @error('seo_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row">
                            <div class="col-lg-2">
                                <label class="form-label mb-0">Hien thi</label>
                            </div>
                            <div class="col-lg-10">
                                <div id="seo-link-preview" class="text-muted">{{ url('/') }}/san-pham/slug</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($type === 'product')
            <div class="card border">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Mat bang du an</h5>
                    <button type="button" id="add-floor-plan" class="btn btn-primary btn-sm">Them mat bang</button>
                </div>
                <div class="card-body">
                    @error('floor_plans')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror
                    <div id="floor-plan-list" class="d-flex flex-column gap-3">
                        @foreach ($floorPlanItems as $index => $floorPlanItem)
                            @php
                                $floorPlanImage = $floorPlanItem['existing_image'] ?? '';
                            @endphp
                            <div class="border rounded p-3 floor-plan-item" data-floor-plan-item>
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <h6 class="mb-0">Mat bang #{{ $loop->iteration }}</h6>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-floor-plan">Xoa</button>
                                </div>
                                <input type="hidden" name="floor_plans[{{ $index }}][id]" value="{{ $floorPlanItem['id'] ?? '' }}">
                                <input type="hidden" name="floor_plans[{{ $index }}][existing_image]" value="{{ $floorPlanImage }}">
                                <div class="row g-3 align-items-start">
                                    <div class="col-md-4">
                                        <input type="file" name="floor_plans[{{ $index }}][image_file]" class="d-none floor-plan-file-input" accept="image/*">
                                        <button type="button" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden p-0 w-100 floor-plan-image-box content-image-dropzone content-image-dropzone--floor-plan">
                                            <img src="{{ $floorPlanImage ? asset($floorPlanImage) : '' }}" alt="Floor plan" class="w-100 h-100 object-fit-cover floor-plan-preview {{ $floorPlanImage ? '' : 'd-none' }}">
                                            <div class="text-center text-muted px-3 floor-plan-placeholder {{ $floorPlanImage ? 'd-none' : '' }}">
                                                <div class="display-6 mb-2"><i class="ri-image-line"></i></div>
                                                <div class="fw-semibold">NO IMAGE</div>
                                                <div>Chon anh mat bang</div>
                                            </div>
                                        </button>
                                        @error("floor_plans.$index.image_file")
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Ten mat bang</label>
                                        <input type="text" name="floor_plans[{{ $index }}][name]" class="form-control @error("floor_plans.$index.name") is-invalid @enderror" value="{{ $floorPlanItem['name'] ?? '' }}" placeholder="Vi du: Mat bang tang 1">
                                        @error("floor_plans.$index.name")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="remove-floor-plans-container"></div>
                    <div class="form-text mt-3">Moi mat bang gom ten va anh. Ban co the them nhieu mat bang cho mot du an.</div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-xl-3">
        <div class="card border">
            <div class="card-body">
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">Khong chon</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('category_id', $post->category_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if ($isCourse)
                    <div class="mb-3">
                        <label for="seller_id" class="form-label">Instructor / Teacher</label>
                        <select id="seller_id" name="seller_id" class="form-select @error('seller_id') is-invalid @enderror">
                            <option value="">Select Instructor / Teacher</option>
                            @foreach ($sellerOptions as $id => $name)
                                <option value="{{ $id }}" {{ (string) old('seller_id', $post->seller_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('seller_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif


                <div class="card border mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ $isCourse ? 'Course Thumbnail' : 'Hinh anh' }}</h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="existing_image" value="{{ $currentImage }}">
                        <input type="hidden" name="remove_image" id="remove_image" value="0">
                        <input type="file" id="image_file" name="image_file" class="d-none" accept="image/*">
                        @if ($type === 'product')
                            <input type="file" id="gallery_files_interior" name="gallery_files_interior[]" class="d-none" accept="image/*" multiple>
                            <input type="file" id="gallery_files_perspective" name="gallery_files_perspective[]" class="d-none" accept="image/*" multiple>
                            <input type="file" id="gallery_files_amenity" name="gallery_files_amenity[]" class="d-none" accept="image/*" multiple>
                        @endif

                        <div class="d-flex flex-column gap-2">
                            <button type="button" id="image-preview-box" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden p-0 w-100 content-image-dropzone content-image-dropzone--cover">
                                <img id="image-preview" src="{{ $imagePreview }}" alt="Preview" class="w-100 h-100 object-fit-cover {{ $imagePreview ? '' : 'd-none' }}">
                                <div id="image-placeholder" class="text-center text-muted px-3 {{ $imagePreview ? 'd-none' : '' }}">
                                    <div class="display-6 mb-2">
                                        <i class="ri-image-line"></i>
                                    </div>
                                    <div class="fw-semibold">NO IMAGE</div>
                                    <div>{{ $isCourse ? 'Click or drop thumbnail here' : 'Click hoac drop anh vao day' }}</div>
                                </div>
                            </button>
                            <button type="button" id="remove-image-trigger" class="btn btn-soft-danger btn-sm align-self-start {{ $imagePreview ? '' : 'd-none' }}">{{ $isCourse ? 'Remove Thumbnail' : 'Bo anh dai dien' }}</button>
                        </div>

                        @error('image_file')
                            <div class="text-danger small mt-3">{{ $message }}</div>
                        @enderror

                        @if (! $isCourse && $type === 'product')
                            @error('gallery_files_interior.*')
                                <div class="text-danger small mt-3">{{ $message }}</div>
                            @enderror
                            @error('gallery_files_perspective.*')
                                <div class="text-danger small mt-3">{{ $message }}</div>
                            @enderror
                            @error('gallery_files_amenity.*')
                                <div class="text-danger small mt-3">{{ $message }}</div>
                            @enderror

                            <div class="mt-3 d-flex flex-column gap-3">
                                @foreach ($galleryTypes as $galleryType => $galleryLabel)
                                    <div class="border rounded p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="fw-semibold">{{ $galleryLabel }}</div>
                                            <button
                                                type="button"
                                                class="btn btn-light border rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 gallery-picker-trigger gallery-picker-button"
                                                data-gallery-type="{{ $galleryType }}"
                                            >
                                                <i class="ri-add-line gallery-picker-icon"></i>
                                            </button>
                                        </div>
                                        <div id="gallery-preview-grid-{{ $galleryType }}" data-gallery-grid="{{ $galleryType }}" class="d-flex align-items-start gap-2 flex-wrap">
                                            @foreach ($galleryImagesByType[$galleryType] as $galleryImage)
                                                <div class="position-relative border rounded overflow-hidden bg-light gallery-item gallery-item-thumb" data-gallery-item>
                                                    <img src="{{ asset($galleryImage->image) }}" alt="Gallery" class="w-100 h-100 object-fit-cover">
                                                    <button
                                                        type="button"
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle d-flex align-items-center justify-content-center remove-gallery-item gallery-remove-button"
                                                        data-existing-id="{{ $galleryImage->id }}"
                                                        data-gallery-type="{{ $galleryType }}"
                                                    >x</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                <div class="form-text mt-0">Moi nhom anh co khu upload rieng. Anh se duoc nen truoc khi upload.</div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isCourse)
                    <div class="card border mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Tài liệu bài học (PDF / PPTX)</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $currentPdf = old('existing_pdf_file', $post->pdf_file ?? '');
                            @endphp
                            <input type="hidden" name="existing_pdf_file" value="{{ $currentPdf }}">
                            <input type="hidden" name="remove_pdf_file" id="remove_pdf_file" value="0">
                            <input type="file" id="pdf_file_input" name="pdf_file_input" class="d-none" accept=".pdf,.pptx,application/pdf,application/vnd.openxmlformats-officedocument.presentationml.presentation">

                            <div class="d-flex flex-column gap-2">
                                <button type="button" id="pdf-preview-box" class="border rounded bg-light d-flex flex-column align-items-center justify-content-center overflow-hidden p-3 w-100 text-center" style="min-height: 140px; border-style: dashed !important; border-color: #cbd5e1 !important;">
                                    <div id="pdf-file-display" class="{{ $currentPdf ? '' : 'd-none' }} w-100">
                                        <div class="display-6 mb-2 text-danger">
                                            <i class="ri-file-pdf-2-line"></i>
                                        </div>
                                        <div class="fw-semibold text-dark text-truncate px-2 mb-1" id="pdf-filename">
                                            {{ $currentPdf ? basename($currentPdf) : '' }}
                                        </div>
                                        <span class="badge bg-soft-success text-success mb-2">Đã tải tài liệu lên</span>
                                        @if ($currentPdf)
                                            <div>
                                                <a href="{{ asset($currentPdf) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1" onclick="event.stopPropagation()">Xem file tài liệu</a>
                                            </div>
                                        @endif
                                    </div>

                                    <div id="pdf-placeholder" class="text-center text-muted px-3 {{ $currentPdf ? 'd-none' : '' }}">
                                        <div class="display-6 mb-2 text-primary">
                                            <i class="ri-upload-cloud-2-line"></i>
                                        </div>
                                        <div class="fw-semibold text-dark">Click hoặc thả file PDF / PPTX vào đây</div>
                                        <div class="small text-muted mt-1">Hỗ trợ tài liệu bài giảng cho học viên</div>
                                    </div>
                                </button>
                                <button type="button" id="remove-pdf-trigger" class="btn btn-soft-danger btn-sm align-self-start {{ $currentPdf ? '' : 'd-none' }}">
                                    Bỏ tài liệu bài giảng
                                </button>
                            </div>

                            @error('pdf_file_input')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $post->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ $isCourse ? 'Active Course' : 'Hien thi ' . strtolower($typeLabel) }}</label>
                </div>

                @if ($isCourse)
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $post->is_featured ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_featured">Featured Course</label>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
