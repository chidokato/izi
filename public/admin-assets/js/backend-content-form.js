document.addEventListener('DOMContentLoaded', function () {
    var formRoot = document.getElementById('backend-content-form');

    if (!formRoot) {
        return;
    }

    var instructorSelect = formRoot.querySelector('#seller_id');

    if (instructorSelect && window.jQuery && window.jQuery.fn.select2) {
        window.jQuery(instructorSelect).select2({
            width: '100%',
            placeholder: 'Select Instructor / Teacher',
            allowClear: true,
            minimumResultsForSearch: 0,
            dropdownParent: window.jQuery(instructorSelect).parent()
        });
    }

    var wardMap = {};

    try {
        wardMap = JSON.parse(formRoot.dataset.wardMap || '{}');
    } catch (error) {
        wardMap = {};
    }

    var urlBase = formRoot.dataset.urlBase || '';
    var slugPrefix = formRoot.dataset.slugPrefix || 'san-pham';
    var fileInput = document.getElementById('image_file');
    var previewBox = document.getElementById('image-preview-box');
    var removeButton = document.getElementById('remove-image-trigger');
    var preview = document.getElementById('image-preview');
    var placeholder = document.getElementById('image-placeholder');
    var removeImageInput = document.getElementById('remove_image');
    var locationFileInput = document.getElementById('location_image_file');
    var locationPreviewBox = document.getElementById('location-image-preview-box');
    var locationRemoveButton = document.getElementById('remove-location-image-trigger');
    var locationPreview = document.getElementById('location-image-preview');
    var locationPlaceholder = document.getElementById('location-image-placeholder');
    var removeLocationImageInput = document.getElementById('remove_location_image');
    var pdfFileInput = document.getElementById('pdf_file_input');
    var pdfPreviewBox = document.getElementById('pdf-preview-box');
    var pdfRemoveButton = document.getElementById('remove-pdf-trigger');
    var pdfFileDisplay = document.getElementById('pdf-file-display');
    var pdfPlaceholder = document.getElementById('pdf-placeholder');
    var pdfFilename = document.getElementById('pdf-filename');
    var removePdfInput = document.getElementById('remove_pdf_file');
    var provinceSelect = document.getElementById('province_id');
    var wardSelect = document.getElementById('ward_id');
    var slugInput = document.getElementById('slug');
    var seoLinkPreview = document.getElementById('seo-link-preview');
    var galleryTypeKeys = ['interior', 'perspective', 'amenity'];
    var galleryInputs = {};
    var galleryGrids = {};
    var galleryFiles = {};
    var floorPlanList = document.getElementById('floor-plan-list');
    var addFloorPlanButton = document.getElementById('add-floor-plan');
    var removeFloorPlansContainer = document.getElementById('remove-floor-plans-container');
    var floorPlanIndex = floorPlanList ? floorPlanList.querySelectorAll('[data-floor-plan-item]').length : 0;

    galleryTypeKeys.forEach(function (typeKey) {
        galleryInputs[typeKey] = document.getElementById('gallery_files_' + typeKey);
        galleryGrids[typeKey] = document.getElementById('gallery-preview-grid-' + typeKey);
        galleryFiles[typeKey] = galleryInputs[typeKey] ? new DataTransfer() : null;
    });

    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            var image = new Image();
            var objectUrl = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(objectUrl);
                resolve(image);
            };

            image.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                reject(new Error('Khong doc duoc anh.'));
            };

            image.src = objectUrl;
        });
    }

    function canvasToBlob(canvas, type, quality) {
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (!blob) {
                    reject(new Error('Khong the nen anh.'));
                    return;
                }

                resolve(blob);
            }, type, quality);
        });
    }

    function renderWardOptions(selectedWardId) {
        if (!wardSelect) {
            return;
        }

        var provinceId = provinceSelect ? provinceSelect.value : '';
        var wards = provinceId && wardMap[provinceId] ? wardMap[provinceId] : [];
        var options = ['<option value="">Chon phuong xa</option>'];

        wards.forEach(function (ward) {
            var selected = String(selectedWardId || '') === String(ward.id) ? ' selected' : '';
            options.push('<option value="' + ward.id + '"' + selected + '>' + ward.name + '</option>');
        });

        wardSelect.innerHTML = options.join('');
        wardSelect.disabled = !provinceId;
    }

    async function compressImage(file, options) {
        var settings = Object.assign({
            maxWidth: 1600,
            maxHeight: 1600,
            quality: 0.82,
            outputType: 'image/webp'
        }, options || {});

        if (!file || !file.type || file.type.indexOf('image/') !== 0 || file.type === 'image/gif') {
            return file;
        }

        var image = await loadImage(file);
        var ratio = Math.min(settings.maxWidth / image.width, settings.maxHeight / image.height, 1);
        var targetWidth = Math.max(1, Math.round(image.width * ratio));
        var targetHeight = Math.max(1, Math.round(image.height * ratio));

        if (ratio === 1 && file.size <= 1024 * 1024) {
            return file;
        }

        var canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;

        var context = canvas.getContext('2d', { alpha: true });
        context.drawImage(image, 0, 0, targetWidth, targetHeight);

        var blob = await canvasToBlob(canvas, settings.outputType, settings.quality);
        var extension = settings.outputType === 'image/png' ? 'png' : 'webp';
        var fileName = (file.name || 'image').replace(/\.[^.]+$/, '') + '.' + extension;

        if (blob.size >= file.size && ratio === 1) {
            return file;
        }

        return new File([blob], fileName, {
            type: blob.type,
            lastModified: Date.now()
        });
    }

    function createDataTransfer(files) {
        var transfer = new DataTransfer();

        (files || []).forEach(function (file) {
            transfer.items.add(file);
        });

        return transfer;
    }

    function bindDropZone(target, handler) {
        if (!target) {
            return;
        }

        ['dragenter', 'dragover'].forEach(function (eventName) {
            target.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                target.classList.add('border-primary');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            target.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                target.classList.remove('border-primary');
            });
        });

        target.addEventListener('drop', function (event) {
            handler(Array.from(event.dataTransfer.files || []));
        });
    }

    function updateFloorPlanLabels() {
        if (!floorPlanList) {
            return;
        }

        floorPlanList.querySelectorAll('[data-floor-plan-item]').forEach(function (item, index) {
            var title = item.querySelector('h6');
            if (title) {
                title.textContent = 'Mat bang #' + (index + 1);
            }
        });
    }

    function bindFloorPlanItem(item) {
        if (!item) {
            return;
        }

        var imageBox = item.querySelector('.floor-plan-image-box');
        var fileInputEl = item.querySelector('.floor-plan-file-input');
        var previewImage = item.querySelector('.floor-plan-preview');
        var placeholderBox = item.querySelector('.floor-plan-placeholder');
        var removeButtonEl = item.querySelector('.remove-floor-plan');

        function showFloorPlanPreview(src) {
            previewImage.src = src;
            previewImage.classList.remove('d-none');
            placeholderBox.classList.add('d-none');
        }

        async function applyFloorPlanImage(file) {
            if (!file) {
                return;
            }

            var compressedFile = await compressImage(file, {
                maxWidth: 1800,
                maxHeight: 1800,
                quality: 0.82
            });
            var transfer = createDataTransfer([compressedFile]);
            fileInputEl.files = transfer.files;

            var reader = new FileReader();
            reader.onload = function (event) {
                showFloorPlanPreview(event.target.result);
            };
            reader.readAsDataURL(compressedFile);
        }

        if (imageBox && fileInputEl) {
            imageBox.addEventListener('click', function () {
                fileInputEl.click();
            });

            fileInputEl.addEventListener('change', function (event) {
                applyFloorPlanImage((event.target.files || [])[0]);
            });

            bindDropZone(imageBox, function (files) {
                applyFloorPlanImage(files[0]);
            });
        }

        if (removeButtonEl) {
            removeButtonEl.addEventListener('click', function () {
                var idInput = item.querySelector('input[name$="[id]"]');
                if (idInput && idInput.value && removeFloorPlansContainer) {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'remove_floor_plans[]';
                    hiddenInput.value = idInput.value;
                    removeFloorPlansContainer.appendChild(hiddenInput);
                }

                item.remove();
                updateFloorPlanLabels();
            });
        }
    }

    function createFloorPlanItem(index) {
        var wrapper = document.createElement('div');
        wrapper.className = 'border rounded p-3 floor-plan-item';
        wrapper.setAttribute('data-floor-plan-item', '');
        wrapper.innerHTML = [
            '<div class="d-flex align-items-start justify-content-between gap-3 mb-3">',
            '<h6 class="mb-0">Mat bang #' + (index + 1) + '</h6>',
            '<button type="button" class="btn btn-outline-danger btn-sm remove-floor-plan">Xoa</button>',
            '</div>',
            '<input type="hidden" name="floor_plans[' + index + '][id]" value="">',
            '<input type="hidden" name="floor_plans[' + index + '][existing_image]" value="">',
            '<div class="row g-3 align-items-start">',
            '<div class="col-md-4">',
            '<input type="file" name="floor_plans[' + index + '][image_file]" class="d-none floor-plan-file-input" accept="image/*">',
            '<button type="button" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden p-0 w-100 floor-plan-image-box content-image-dropzone content-image-dropzone--floor-plan">',
            '<div class="text-center text-muted px-3 floor-plan-placeholder">',
            '<div class="display-6 mb-2"><i class="ri-image-line"></i></div>',
            '<div class="fw-semibold">NO IMAGE</div>',
            '<div>Chon anh mat bang</div>',
            '</div>',
            '<img src="" alt="Floor plan" class="w-100 h-100 object-fit-cover floor-plan-preview d-none">',
            '</button>',
            '</div>',
            '<div class="col-md-8">',
            '<label class="form-label">Ten mat bang</label>',
            '<input type="text" name="floor_plans[' + index + '][name]" class="form-control" placeholder="Vi du: Mat bang tang 1">',
            '</div>',
            '</div>'
        ].join('');

        bindFloorPlanItem(wrapper);
        return wrapper;
    }

    function showImagePlaceholder(targetPreview, targetPlaceholder, targetRemoveButton) {
        targetPreview.src = '';
        targetPreview.classList.add('d-none');
        targetPlaceholder.classList.remove('d-none');
        targetRemoveButton.classList.add('d-none');
    }

    function showImagePreview(targetPreview, targetPlaceholder, targetRemoveButton, src) {
        targetPreview.src = src;
        targetPreview.classList.remove('d-none');
        targetPlaceholder.classList.add('d-none');
        targetRemoveButton.classList.remove('d-none');
    }

    async function applySingleImage(file, targetFileInput, targetRemoveInput, onPreview) {
        if (!file) {
            return;
        }

        var compressedFile = await compressImage(file, {
            maxWidth: 1800,
            maxHeight: 1800,
            quality: 0.82
        });
        var transfer = createDataTransfer([compressedFile]);

        targetRemoveInput.value = '0';
        targetFileInput.files = transfer.files;

        var reader = new FileReader();
        reader.onload = function (event) {
            onPreview(event.target.result);
        };
        reader.readAsDataURL(compressedFile);
    }

    if (fileInput && previewBox && preview && placeholder && removeButton && removeImageInput) {
        previewBox.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function (event) {
            var file = event.target.files[0];

            if (file) {
                applySingleImage(file, fileInput, removeImageInput, function (src) {
                    showImagePreview(preview, placeholder, removeButton, src);
                });
            }
        });

        removeButton.addEventListener('click', function () {
            fileInput.value = '';
            removeImageInput.value = '1';
            showImagePlaceholder(preview, placeholder, removeButton);
        });

        bindDropZone(previewBox, function (files) {
            applySingleImage(files[0], fileInput, removeImageInput, function (src) {
                showImagePreview(preview, placeholder, removeButton, src);
            });
        });
    }

    if (locationFileInput && locationPreviewBox && locationPreview && locationPlaceholder && locationRemoveButton && removeLocationImageInput) {
        locationPreviewBox.addEventListener('click', function () {
            locationFileInput.click();
        });

        locationFileInput.addEventListener('change', function (event) {
            var file = event.target.files[0];

            if (file) {
                applySingleImage(file, locationFileInput, removeLocationImageInput, function (src) {
                    showImagePreview(locationPreview, locationPlaceholder, locationRemoveButton, src);
                });
            }
        });

        locationRemoveButton.addEventListener('click', function () {
            locationFileInput.value = '';
            removeLocationImageInput.value = '1';
            showImagePlaceholder(locationPreview, locationPlaceholder, locationRemoveButton);
        });

        bindDropZone(locationPreviewBox, function (files) {
            applySingleImage(files[0], locationFileInput, removeLocationImageInput, function (src) {
                showImagePreview(locationPreview, locationPlaceholder, locationRemoveButton, src);
            });
        });
    }

    function showPdfFileDisplay(filename) {
        if (pdfFilename) {
            pdfFilename.textContent = filename;
        }
        if (pdfFileDisplay) {
            pdfFileDisplay.classList.remove('d-none');
        }
        if (pdfPlaceholder) {
            pdfPlaceholder.classList.add('d-none');
        }
        if (pdfRemoveButton) {
            pdfRemoveButton.classList.remove('d-none');
        }
    }

    function showPdfPlaceholder() {
        if (pdfFileDisplay) {
            pdfFileDisplay.classList.add('d-none');
        }
        if (pdfPlaceholder) {
            pdfPlaceholder.classList.remove('d-none');
        }
        if (pdfRemoveButton) {
            pdfRemoveButton.classList.add('d-none');
        }
    }

    function applySinglePdfFile(file) {
        if (!file) {
            return;
        }
        var transfer = createDataTransfer([file]);
        pdfFileInput.files = transfer.files;
        if (removePdfInput) {
            removePdfInput.value = '0';
        }
        showPdfFileDisplay(file.name);
    }

    if (pdfFileInput && pdfPreviewBox) {
        pdfPreviewBox.addEventListener('click', function () {
            pdfFileInput.click();
        });

        pdfFileInput.addEventListener('change', function (event) {
            var file = (event.target.files || [])[0];
            if (file) {
                applySinglePdfFile(file);
            }
        });

        if (pdfRemoveButton) {
            pdfRemoveButton.addEventListener('click', function (event) {
                event.stopPropagation();
                pdfFileInput.value = '';
                if (removePdfInput) {
                    removePdfInput.value = '1';
                }
                showPdfPlaceholder();
            });
        }

        bindDropZone(pdfPreviewBox, function (files) {
            if (files && files[0]) {
                applySinglePdfFile(files[0]);
            }
        });
    }

    if (provinceSelect && wardSelect) {
        renderWardOptions(wardSelect.value);

        provinceSelect.addEventListener('change', function () {
            renderWardOptions('');
        });
    }

    function updateSeoPreview() {
        if (!slugInput || !seoLinkPreview) {
            return;
        }

        var slug = slugInput.value || 'slug';
        seoLinkPreview.textContent = urlBase + '/' + slugPrefix + '/' + slug;
    }

    if (slugInput) {
        slugInput.addEventListener('input', updateSeoPreview);
    }
    updateSeoPreview();

    function appendGalleryItem(typeKey, src, existingId, fileToken) {
        var galleryGrid = galleryGrids[typeKey];

        if (!galleryGrid) {
            return;
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'position-relative border rounded overflow-hidden bg-light gallery-item gallery-item-thumb';
        wrapper.setAttribute('data-gallery-item', '');

        var image = document.createElement('img');
        image.src = src;
        image.className = 'w-100 h-100 object-fit-cover';
        image.alt = 'Gallery';

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle d-flex align-items-center justify-content-center remove-gallery-item gallery-remove-button';
        button.textContent = 'x';

        if (existingId) {
            button.dataset.existingId = existingId;
        }

        if (fileToken) {
            button.dataset.fileToken = fileToken;
        }

        button.dataset.galleryType = typeKey;

        wrapper.appendChild(image);
        wrapper.appendChild(button);
        galleryGrid.appendChild(wrapper);
    }

    async function addGalleryFiles(typeKey, files) {
        var galleryInput = galleryInputs[typeKey];
        var galleryTransfer = galleryFiles[typeKey];

        if (!galleryInput || !galleryTransfer) {
            return;
        }

        for (let index = 0; index < files.length; index += 1) {
            let compressedFile = await compressImage(files[index], {
                maxWidth: 1800,
                maxHeight: 1800,
                quality: 0.8
            });
            let currentToken = [compressedFile.name, compressedFile.size, compressedFile.lastModified].join('__');
            galleryTransfer.items.add(compressedFile);

            var reader = new FileReader();
            reader.onload = function (event) {
                appendGalleryItem(typeKey, event.target.result, null, currentToken);
            };
            reader.readAsDataURL(compressedFile);
        }

        galleryInput.files = galleryTransfer.files;
    }

    document.querySelectorAll('.gallery-picker-trigger').forEach(function (button) {
        var typeKey = button.getAttribute('data-gallery-type');
        var galleryInput = galleryInputs[typeKey];
        var galleryGrid = galleryGrids[typeKey];

        if (!galleryInput || !galleryGrid) {
            return;
        }

        button.addEventListener('click', function () {
            galleryInput.click();
        });

        galleryInput.addEventListener('change', function (event) {
            addGalleryFiles(typeKey, Array.from(event.target.files || []));
        });

        bindDropZone(galleryGrid, function (files) {
            addGalleryFiles(typeKey, files);
        });
    });

    document.addEventListener('click', function (event) {
        var removeGalleryButton = event.target.closest('.remove-gallery-item');

        if (!removeGalleryButton) {
            return;
        }

        var existingId = removeGalleryButton.getAttribute('data-existing-id');
        var fileToken = removeGalleryButton.getAttribute('data-file-token');
        var galleryType = removeGalleryButton.getAttribute('data-gallery-type');
        var galleryGrid = galleryGrids[galleryType];
        var galleryInput = galleryInputs[galleryType];
        var galleryTransfer = galleryFiles[galleryType];

        if (existingId && galleryGrid) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'remove_gallery_images[]';
            input.value = existingId;
            galleryGrid.appendChild(input);
        }

        if (fileToken && galleryInput && galleryTransfer) {
            var nextFiles = new DataTransfer();

            Array.from(galleryInput.files).forEach(function (file) {
                var currentToken = [file.name, file.size, file.lastModified].join('__');

                if (currentToken !== fileToken) {
                    nextFiles.items.add(file);
                }
            });

            galleryFiles[galleryType] = nextFiles;
            galleryInput.files = galleryFiles[galleryType].files;
        }

        var galleryItem = removeGalleryButton.closest('[data-gallery-item]');
        if (galleryItem) {
            galleryItem.remove();
        }
    });

    if (floorPlanList) {
        floorPlanList.querySelectorAll('[data-floor-plan-item]').forEach(function (item) {
            bindFloorPlanItem(item);
        });
        updateFloorPlanLabels();
    }

    if (addFloorPlanButton && floorPlanList) {
        addFloorPlanButton.addEventListener('click', function () {
            floorPlanList.appendChild(createFloorPlanItem(floorPlanIndex));
            floorPlanIndex += 1;
            updateFloorPlanLabels();
        });
    }
});
