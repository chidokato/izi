<div class="col-lg-4">
    <div class="border rounded p-3 h-100">
        <div class="fw-semibold mb-3">{{ $title }}</div>
        <input type="hidden" name="{{ $removeField }}" id="{{ $removeField }}" value="0">
        <input type="file" id="{{ $field }}" name="{{ $field }}" class="d-none" accept="image/*">

        <div class="d-flex flex-column gap-2">
            <button type="button" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden p-0 image-upload-trigger" data-input="{{ $field }}" style="height: 160px;">
                @if ($image)
                    <img src="{{ asset($image) }}" alt="{{ $title }}" class="w-100 h-100 object-fit-contain">
                @else
                    <div class="text-center text-muted">
                        <div class="display-6 mb-2"><i class="ri-image-line"></i></div>
                        <div>No image</div>
                    </div>
                @endif
            </button>
            <button type="button" class="btn btn-soft-danger btn-sm image-remove-trigger {{ $image ? '' : 'd-none' }}" data-input="{{ $field }}" data-remove="{{ $removeField }}">Bo anh</button>
        </div>

        @error($field)
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror
    </div>
</div>
