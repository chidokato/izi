@csrf

<div class="row">
    <div class="col-xl-9">
        <div class="card border">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label for="name" class="form-label">Ten category</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name ?? '') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug ?? '') }}">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-0">
                            <label for="description" class="form-label">Mo ta</label>
                            <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
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
                            <input type="text" id="seo_title" name="seo_title" class="form-control @error('seo_title') is-invalid @enderror" value="{{ old('seo_title', $category->seo_title ?? '') }}" placeholder="Nhap SEO title">
                            @error('seo_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="seo_description" class="form-label">Description</label>
                            <textarea id="seo_description" name="seo_description" rows="3" class="form-control @error('seo_description') is-invalid @enderror" placeholder="Nhap SEO description">{{ old('seo_description', $category->seo_description ?? '') }}</textarea>
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
                                <div id="seo-link-preview" class="text-muted">{{ url('/') }}/danh-muc-san-pham/slug</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <div class="card border">
            <div class="card-body">
                <div class="mb-3">
                    <label for="type" class="form-label">Loai category</label>
                    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" {{ old('type', $category->type ?? request('type', 'product')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">Danh muc cha</label>
                    <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">Khong co</option>
                        @foreach (($parentOptions[old('type', $category->type ?? request('type', 'product'))] ?? []) as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('parent_id', $category->parent_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="sort_order" class="form-label">Thu tu</label>
                    <input type="number" min="0" id="sort_order" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Hien thi category</label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var typeSelect = document.getElementById('type');
        var parentSelect = document.getElementById('parent_id');
        var slugInput = document.getElementById('slug');
        var seoLinkPreview = document.getElementById('seo-link-preview');
        var optionsByType = @json($parentOptions);

        if (!typeSelect || !parentSelect) {
            return;
        }

        function renderParentOptions() {
            var selectedType = typeSelect.value;
            var selectedParent = '{{ (string) old('parent_id', $category->parent_id ?? '') }}';
            var options = optionsByType[selectedType] || {};

            parentSelect.innerHTML = '<option value="">Khong co</option>';

            Object.keys(options).forEach(function (id) {
                var option = document.createElement('option');
                option.value = id;
                option.textContent = options[id];

                if (id === selectedParent) {
                    option.selected = true;
                }

                parentSelect.appendChild(option);
            });
        }

        function updateSeoPreview() {
            if (!seoLinkPreview || !slugInput || !typeSelect) {
                return;
            }

            var slug = slugInput.value || 'slug';
            var prefix = typeSelect.value === 'news' ? 'danh-muc-tin-tuc' : 'danh-muc-san-pham';
            seoLinkPreview.textContent = '{{ url('/') }}/' + prefix + '/' + slug;
        }

        typeSelect.addEventListener('change', function () {
            renderParentOptions();
            updateSeoPreview();
        });

        slugInput.addEventListener('input', updateSeoPreview);

        renderParentOptions();
        updateSeoPreview();
    });
</script>
