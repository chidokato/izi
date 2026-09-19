document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.image-upload-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            var inputId = button.getAttribute('data-input');
            var input = document.getElementById(inputId);

            if (input) {
                input.click();
            }
        });
    });

    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function (event) {
            var file = event.target.files[0];
            var trigger = document.querySelector('.image-upload-trigger[data-input="' + input.id + '"]');
            var removeButton = document.querySelector('.image-remove-trigger[data-input="' + input.id + '"]');

            if (!file || !trigger) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                trigger.innerHTML = '<img src="' + e.target.result + '" class="w-100 h-100 object-fit-contain" alt="preview">';
                if (removeButton) {
                    removeButton.classList.remove('d-none');
                }
            };
            reader.readAsDataURL(file);
        });
    });

    document.querySelectorAll('.image-remove-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            var inputId = button.getAttribute('data-input');
            var removeFieldId = button.getAttribute('data-remove');
            var input = document.getElementById(inputId);
            var removeField = document.getElementById(removeFieldId);
            var trigger = document.querySelector('.image-upload-trigger[data-input="' + inputId + '"]');

            if (input) {
                input.value = '';
            }

            if (removeField) {
                removeField.value = '1';
            }

            if (trigger) {
                trigger.innerHTML = '<div class="text-center text-muted"><div class="display-6 mb-2"><i class="ri-image-line"></i></div><div>No image</div></div>';
            }

            button.classList.add('d-none');
        });
    });
});
