(function () {
    const form = document.getElementById('roomForm');
    const nameInput = document.getElementById('room_name');
    const tagSelect = document.getElementById('room_tag');
    const radios = form.querySelectorAll('input[name="room_icon"]');
    const pvName = document.getElementById('previewName');
    const pvTag = document.getElementById('previewTag');
    const pvIcon = document.getElementById('previewIcon');

    function updatePreview() {
        pvName.textContent = nameInput.value.trim() || 'Room name';
        pvTag.textContent = tagSelect.value || 'Select a floor';

        const checked = form.querySelector('input[name="room_icon"]:checked');
        if (checked) {
            pvIcon.innerHTML = checked.closest('.icon-option').querySelector('svg').outerHTML;
            pvIcon.classList.add('filled');
        } else {
            pvIcon.innerHTML = '';
            pvIcon.classList.remove('filled');
        }
    }

    nameInput.addEventListener('input', updatePreview);
    tagSelect.addEventListener('change', updatePreview);
    radios.forEach(function (r) { r.addEventListener('change', updatePreview); });
    updatePreview();

    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
        }
    });
})();
