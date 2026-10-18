(function () {
    const form = document.getElementById('staffForm');
    const pw = document.getElementById('password');

    document.getElementById('showPassword').addEventListener('change', function () {
        pw.type = this.checked ? 'text' : 'password';
    });

    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
        }
    });
})();
