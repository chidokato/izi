document.addEventListener('DOMContentLoaded', function () {
    var config = document.getElementById('backend-app-config');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    window.uploadUrl = config ? config.dataset.uploadUrl || '' : '';

    function showToast(icon, title, text) {
        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            toast: true,
            position: 'bottom-start',
            icon: icon,
            title: title,
            text: text,
            timer: 2200,
            timerProgressBar: true,
            showConfirmButton: false,
            showCloseButton: true
        });
    }

    window.showBackendToast = showToast;

    if (config && config.dataset.successMessage) {
        showToast('success', 'Thanh cong', config.dataset.successMessage);
    }

    if (config && config.dataset.errorMessage) {
        showToast('error', 'Co loi xay ra', config.dataset.errorMessage);
    }

    if (config && config.dataset.validationMessage) {
        showToast('warning', 'Vui long kiem tra du lieu', config.dataset.validationMessage);
    }

    document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var message = form.getAttribute('data-confirm-message') || 'Ban co chac muon xoa muc nay?';

            Swal.fire({
                icon: 'warning',
                title: 'Xac nhan xoa',
                text: message,
                showCancelButton: true,
                confirmButtonText: 'Xoa',
                cancelButtonText: 'Huy',
                confirmButtonColor: '#f06548',
                cancelButtonColor: '#405189'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('[data-toggle-status]').forEach(function (button) {
        button.addEventListener('click', function () {
            var url = button.getAttribute('data-url');
            var label = button.parentElement ? button.parentElement.querySelector('[data-status-label]') : null;

            if (!url) {
                return;
            }

            button.disabled = true;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Toggle status failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    button.classList.toggle('is-active', data.is_active);
                    button.classList.toggle('is-inactive', !data.is_active);
                    button.setAttribute('aria-pressed', data.is_active ? 'true' : 'false');

                    if (label) {
                        label.textContent = data.label;
                        label.classList.toggle('text-success', data.is_active);
                        label.classList.toggle('text-danger', !data.is_active);
                    }

                    showToast('success', 'Da cap nhat', data.message);
                })
                .catch(function () {
                    showToast('error', 'Khong cap nhat duoc', 'Vui long thu lai.');
                })
                .finally(function () {
                    button.disabled = false;
                });
        });
    });

    document.querySelectorAll('[data-update-sort-order]').forEach(function (input) {
        var submitSortOrder = function () {
            var url = input.getAttribute('data-url');
            var currentValue = input.value.trim();
            var initialValue = input.getAttribute('data-initial-value');

            if (!url || currentValue === '' || currentValue === initialValue) {
                if (currentValue === '') {
                    input.value = initialValue || 0;
                }

                return;
            }

            input.disabled = true;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    sort_order: Number(currentValue)
                })
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Update sort order failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    input.value = data.sort_order;
                    input.setAttribute('data-initial-value', data.sort_order);
                    showToast('success', 'Da cap nhat', data.message);
                })
                .catch(function () {
                    input.value = initialValue || 0;
                    showToast('error', 'Khong cap nhat duoc', 'Vui long thu lai.');
                })
                .finally(function () {
                    input.disabled = false;
                });
        };

        input.addEventListener('blur', submitSortOrder);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                input.blur();
            }
        });
    });

    if (typeof initEditor === 'function') {
        initEditor();
    }
});
