<script>
(function () {
    function bindSinglePreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if (!input || !preview) {
            return;
        }

        const render = function () {
            preview.innerHTML = '';
            const file = input.files && input.files[0];
            if (!file) {
                return;
            }
            const url = URL.createObjectURL(file);
            const wrap = document.createElement('div');
            wrap.className = 'relative inline-block';
            wrap.innerHTML =
                '<img src="' + url + '" class="h-24 w-24 object-cover rounded border" alt="">' +
                '<button type="button" class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-red-600 text-white text-sm leading-6 text-center">×</button>';
            wrap.querySelector('button').addEventListener('click', function () {
                input.value = '';
                preview.innerHTML = '';
            });
            preview.appendChild(wrap);
        };

        input.addEventListener('change', render);
    }

    function bindGalleryPreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if (!input || !preview || !window.DataTransfer) {
            return;
        }

        let files = [];
        let syncing = false;

        const sync = function () {
            const dt = new DataTransfer();
            files.forEach(function (file) {
                dt.items.add(file);
            });
            syncing = true;
            input.files = dt.files;
            syncing = false;
            preview.innerHTML = '';
            files.forEach(function (file, index) {
                const url = URL.createObjectURL(file);
                const wrap = document.createElement('div');
                wrap.className = 'relative';
                wrap.innerHTML =
                    '<img src="' + url + '" class="h-24 w-24 object-cover rounded border" alt="">' +
                    '<button type="button" data-index="' + index + '" class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-red-600 text-white text-sm leading-6 text-center">×</button>';
                wrap.querySelector('button').addEventListener('click', function () {
                    files.splice(index, 1);
                    sync();
                });
                preview.appendChild(wrap);
            });
        };

        input.addEventListener('change', function () {
            if (syncing) {
                return;
            }
            files = files.concat(Array.from(input.files || []));
            sync();
        });
    }

    bindSinglePreview('image_path', 'main-image-preview');
    bindGalleryPreview('images', 'gallery-preview');
})();
</script>
