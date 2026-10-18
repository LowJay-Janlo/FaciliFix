(function () {
    const form = document.getElementById('equipmentForm');
    const desc = document.getElementById('description');
    const count = document.getElementById('charCount');

    function updateCount() { count.textContent = desc.value.length; }
    desc.addEventListener('input', updateCount);
    updateCount();

    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
        }
    });

    // ---------- Photo picker ----------
    const MAX_BYTES = 3 * 1024 * 1024;
    const input = document.getElementById('image');
    const previewBox = document.getElementById('uploadPreview');
    const image = document.getElementById('uploadImage');
    const label = document.getElementById('uploadLabel');
    const clearButton = document.getElementById('uploadClear');
    const removeLabel = document.getElementById('removeLabel');
    const removeCheck = document.getElementById('removeImage');
    const errorNote = document.getElementById('uploadError');
    const original = image.dataset.original || '';
    let blobUrl = '';

    function showError(text) {
        errorNote.textContent = text;
        errorNote.hidden = !text;
    }

    function release() {
        if (blobUrl) { URL.revokeObjectURL(blobUrl); blobUrl = ''; }
    }

    // Back to "no new photo chosen"
    function reset() {
        release();
        input.value = '';
        clearButton.hidden = true;
        label.textContent = label.dataset.default;
        if (removeLabel) { removeLabel.hidden = false; }
        if (original) { image.src = original; } else { image.removeAttribute('src'); }
        previewBox.hidden = !original;
        previewBox.classList.toggle('removing', !!(removeCheck && removeCheck.checked));
    }

    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        showError('');
        if (!file) { reset(); return; }

        if (['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) {
            reset();
            showError('Please choose a JPG, PNG or WebP image.');
            return;
        }
        if (file.size > MAX_BYTES) {
            reset();
            showError('That image is larger than 3 MB. Please choose a smaller one.');
            return;
        }

        release();
        blobUrl = URL.createObjectURL(file);
        image.src = blobUrl;
        previewBox.hidden = false;
        previewBox.classList.remove('removing');
        label.textContent = file.name;
        clearButton.hidden = false;
        // A new photo replaces the old one, so "remove" no longer applies
        if (removeCheck) { removeCheck.checked = false; }
        if (removeLabel) { removeLabel.hidden = true; }
    });

    clearButton.addEventListener('click', function () { showError(''); reset(); });

    if (removeCheck) {
        removeCheck.addEventListener('change', function () {
            previewBox.classList.toggle('removing', removeCheck.checked);
        });
    }

    reset();
})();
