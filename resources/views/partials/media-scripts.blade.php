{{--
    Skrip bersama manajemen gambar (dipakai halaman "Tampilan Toko" dan
    galeri produk):
    1. Preview sebelum simpan - thumbnail berkas terpilih dari input.
    2. Optimasi ukuran di browser - gambar besar diperkecil ke lebar
       maksimum sebelum dikirim, jadi halaman tidak lambat.
    3. Pengurutan galeri - tombol atas/bawah memindah kartu lalu
       mengirim urutan id ke route admin.media.reorder.
--}}
<script>
window.MediaUI = (function () {
    'use strict';

    var MAX_WIDTH = @json((int) config('media.max_width', 1600));
    var SHRINK_ABOVE = 400 * 1024; // hanya berkas > 400 KB yang diproses
    var QUALITY = 0.82;

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /**
     * Tampilkan thumbnail berkas terpilih ke elemen preview.
     */
    function renderPreview(input) {
        var target = document.getElementById(input.dataset.preview);
        if (!target) {
            return;
        }

        target.innerHTML = '';

        Array.from(input.files || []).forEach(function (file) {
            if (!file.type || file.type.indexOf('image/') !== 0) {
                return;
            }

            var wrap = document.createElement('div');
            wrap.className = 'position-relative border rounded p-1 bg-light';
            wrap.style.width = '72px';

            var img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'rounded d-block';
            img.style.width = '64px';
            img.style.height = '64px';
            img.style.objectFit = 'cover';
            img.alt = file.name;

            wrap.appendChild(img);
            target.appendChild(wrap);
        });
    }

    /**
     * Perkecil gambar besar di browser (tetap satu format, kualitas
     * setara). Berkas kecil tidak diproses.
     */
    function shrink(file) {
        return new Promise(function (resolve) {
            if (!file.type || file.type.indexOf('image/') !== 0 || file.size <= SHRINK_ABOVE) {
                resolve(file);
                return;
            }

            var url = URL.createObjectURL(file);
            var img = new Image();

            img.onload = function () {
                try {
                    var scale = Math.min(1, MAX_WIDTH / img.naturalWidth);

                    if (scale >= 1) {
                        resolve(file);
                        return;
                    }

                    var canvas = document.createElement('canvas');
                    canvas.width = Math.round(img.naturalWidth * scale);
                    canvas.height = Math.round(img.naturalHeight * scale);
                    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);

                    var type = file.type;
                    var mimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (mimeTypes.indexOf(type) === -1) {
                        type = 'image/jpeg';
                    }

                    canvas.toBlob(function (blob) {
                        URL.revokeObjectURL(url);

                        // Pakai hasil kompresi hanya jika benar-benar lebih kecil.
                        if (!blob || blob.size >= file.size) {
                            resolve(file);
                            return;
                        }

                        var nameParts = file.name.split('.');
                        var extension = type === 'image/jpeg' ? 'jpg' : (type === 'image/png' ? 'png' : 'webp');
                        nameParts[nameParts.length - 1] = extension;

                        resolve(new File([blob], nameParts.join('.'), { type: type }));
                    }, type, QUALITY);
                } catch (error) {
                    URL.revokeObjectURL(url);
                    resolve(file);
                }
            };

            img.onerror = function () {
                URL.revokeObjectURL(url);
                resolve(file);
            };

            img.src = url;
        });
    }

    /**
     * Sebelum form unggah dikirim, perkecil dulu gambar besar di browser.
     */
    function bindUploadForm(form) {
        Array.from(form.querySelectorAll('input[type="file"][data-preview]')).forEach(function (input) {
            input.addEventListener('change', function () {
                renderPreview(input);
            });
        });

        form.addEventListener('submit', function (event) {
            var inputs = Array.from(form.querySelectorAll('input[type="file"]'))
                .filter(function (input) {
                    return (input.files || []).length > 0;
                });

            if (inputs.length === 0) {
                return;
            }

            event.preventDefault();

            Promise.all(inputs.map(function (input) {
                return Promise.all(Array.from(input.files).map(shrink)).then(function (files) {
                    var transfer = new DataTransfer();
                    files.forEach(function (file) {
                        transfer.items.add(file);
                    });
                    input.files = transfer.files;
                });
            })).then(function () {
                // Hindari submit dua kali.
                form.submit();
            });
        });
    }

    /**
     * Kirim urutan id media pada satu grup galeri.
     */
    function submitOrder(group) {
        var formId = group.dataset.orderGroup;
        var form = document.getElementById(formId);

        if (!form) {
            return;
        }

        var ids = Array.from(group.querySelectorAll('[data-media-id]'))
            .map(function (el) {
                return el.dataset.mediaId;
            });

        if (ids.length === 0) {
            return;
        }

        form.querySelectorAll('input[name="ids[]"]').forEach(function (el) {
            el.remove();
        });

        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });

        form.submit();
    }

    /**
     * Pasang tombol geser atas/bawah pada satu grup galeri.
     */
    function bindOrderGroup(group) {
        Array.from(group.querySelectorAll('[data-move]')).forEach(function (button) {
            button.addEventListener('click', function () {
                var item = button.closest('[data-media-id]');

                if (!item) {
                    return;
                }

                var up = button.dataset.move === 'up';
                var sibling = up ? item.previousElementSibling : item.nextElementSibling;

                if (!sibling) {
                    return;
                }

                if (up) {
                    item.parentNode.insertBefore(item, sibling);
                } else {
                    item.parentNode.insertBefore(sibling, item);
                }

                submitOrder(group);
            });
        });
    }

    function init() {
        Array.from(document.querySelectorAll('form[data-media-upload]')).forEach(bindUploadForm);
        Array.from(document.querySelectorAll('[data-order-group]')).forEach(bindOrderGroup);
    }

    return { init: init };
})();

document.addEventListener('DOMContentLoaded', function () {
    window.MediaUI.init();
});
</script>
