function initEditor() {
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function loadImageDimensions(file) {
        return new Promise(function (resolve, reject) {
            var image = new Image();
            var objectUrl = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(objectUrl);
                resolve({
                    width: image.width,
                    height: image.height
                });
            };

            image.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                reject(new Error('Khong doc duoc kich thuoc anh.'));
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

    async function compressImage(file, options) {
        var settings = Object.assign({
            maxWidth: 1600,
            maxHeight: 1600,
            quality: 0.82,
            outputType: 'image/webp'
        }, options || {});

        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
            return file;
        }

        if (file.type === 'image/gif') {
            return file;
        }

        var dimensions = await loadImageDimensions(file);
        var width = dimensions.width;
        var height = dimensions.height;
        var ratio = Math.min(settings.maxWidth / width, settings.maxHeight / height, 1);
        var targetWidth = Math.max(1, Math.round(width * ratio));
        var targetHeight = Math.max(1, Math.round(height * ratio));

        if (ratio === 1 && file.size <= 1024 * 1024) {
            return file;
        }

        var image = new Image();
        var objectUrl = URL.createObjectURL(file);

        try {
            await new Promise(function (resolve, reject) {
                image.onload = resolve;
                image.onerror = reject;
                image.src = objectUrl;
            });

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
        } finally {
            URL.revokeObjectURL(objectUrl);
        }
    }

    function createUploadAdapter(loader) {
        return {
            upload: function () {
                return loader.file.then(async function (file) {
                    if (!window.uploadUrl) {
                        throw new Error('Upload URL is not configured.');
                    }

                    var compressedFile = await compressImage(file, {
                        maxWidth: 1800,
                        maxHeight: 1800,
                        quality: 0.8
                    });

                    var formData = new FormData();
                    formData.append('upload', compressedFile);

                    var response = await fetch(window.uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    var data = await response.json();

                    if (!response.ok || !data.url) {
                        throw new Error(data.message || 'Khong upload duoc anh.');
                    }

                    return {
                        default: data.url
                    };
                });
            },
            abort: function () {}
        };
    }

    function uploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
            return createUploadAdapter(loader);
        };
    }

    document.querySelectorAll('.editor').forEach(function (editorElement) {
        var config = {
            extraPlugins: [uploadAdapterPlugin],
            toolbar: {
                items: [
                    'exportPDF', 'exportWord', '|',
                    'findAndReplace', 'selectAll', '|',
                    'heading', '|',
                    'bold', 'italic', 'strikethrough', 'underline', 'code', 'subscript', 'superscript', 'removeFormat', '|',
                    'bulletedList', 'numberedList', 'todoList', '|',
                    'outdent', 'indent', '|',
                    'undo', 'redo',
                    '-',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                    'alignment', '|',
                    'link', 'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed', 'codeBlock', 'htmlEmbed', '|',
                    'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                    'textPartLanguage', '|',
                    'sourceEditing'
                ],
                shouldNotGroupWhenFull: true
            },
            list: {
                properties: {
                    styles: true,
                    startIndex: true,
                    reversed: true
                }
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                    { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                    { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
                ]
            },
            placeholder: 'Keo tha, dan hoac chen noi dung tai day...',
            fontFamily: {
                options: [
                    'default',
                    'Arial, Helvetica, sans-serif',
                    'Courier New, Courier, monospace',
                    'Georgia, serif',
                    'Lucida Sans Unicode, Lucida Grande, sans-serif',
                    'Tahoma, Geneva, sans-serif',
                    'Times New Roman, Times, serif',
                    'Trebuchet MS, Helvetica, sans-serif',
                    'Verdana, Geneva, sans-serif'
                ],
                supportAllValues: true
            },
            fontSize: {
                options: [10, 12, 14, 'default', 18, 20, 22],
                supportAllValues: true
            },
            htmlSupport: {
                allow: [
                    {
                        name: /.*/,
                        attributes: true,
                        classes: false,
                        styles: false
                    }
                ]
            },
            htmlEmbed: {
                showPreviews: false
            },
            link: {
                decorators: {
                    addTargetToExternalLinks: true,
                    defaultProtocol: 'https://',
                    toggleDownloadable: {
                        mode: 'manual',
                        label: 'Downloadable',
                        attributes: {
                            download: 'file'
                        }
                    }
                }
            },
            mention: {
                feeds: [
                    {
                        marker: '@',
                        feed: [
                            '@apple', '@bears', '@brownie', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                            '@cupcake', '@danish', '@donut', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                            '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps',
                            '@sugar', '@sweet', '@topping', '@wafer'
                        ],
                        minimumCharacters: 1
                    }
                ]
            },
            removePlugins: [
                'AIAssistant',
                'CKBox',
                'EasyImage',
                'MultiLevelList',
                'RealTimeCollaborativeComments',
                'RealTimeCollaborativeTrackChanges',
                'RealTimeCollaborativeRevisionHistory',
                'PresenceList',
                'Comments',
                'TrackChanges',
                'TrackChangesData',
                'RevisionHistory',
                'Pagination',
                'WProofreader',
                'MathType',
                'SlashCommand',
                'Template',
                'DocumentOutline',
                'FormatPainter',
                'TableOfContents',
                'PasteFromOfficeEnhanced',
                'CaseChange'
            ]
        };

        CKEDITOR.ClassicEditor.create(editorElement, config)
            .then(function (editor) {
                editorElement.ckeditorInstance = editor;
            })
            .catch(function (error) {
                console.error('CKEditor init error:', error);
            });
    });
}
