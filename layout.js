/* ============================================
   FaciliFix shared layout script
   - Sidebar show / hide
   - Confirmation dialog used by:
       * every link to logout.php
       * any button with a data-confirm="..." attribute (delete buttons)
   ============================================ */
(function () {
    var body = document.body;

    // ---------- Sidebar show / hide ----------
    var toggle = document.getElementById('sidebarToggle');
    if (toggle) {
        if (window.innerWidth < 800) body.classList.add('sidebar-collapsed');

        toggle.addEventListener('click', function () {
            var collapsed = body.classList.toggle('sidebar-collapsed');
            toggle.setAttribute('aria-expanded', String(!collapsed));
        });
    }

    // ---------- Confirmation dialog ----------
    var dialog = null;
    var titleEl, textEl, okBtn;
    var pendingOk = null;

    function build() {
        if (dialog) return;
        dialog = document.createElement('dialog');
        dialog.className = 'confirm-dialog';
        dialog.setAttribute('aria-labelledby', 'confirmTitle');
        dialog.innerHTML =
            '<h2 id="confirmTitle"></h2>' +
            '<p></p>' +
            '<div class="confirm-actions">' +
                '<button type="button" class="confirm-cancel" autofocus>Cancel</button>' +
                '<button type="button" class="confirm-ok"></button>' +
            '</div>';
        document.body.appendChild(dialog);

        titleEl = dialog.querySelector('h2');
        textEl = dialog.querySelector('p');
        okBtn = dialog.querySelector('.confirm-ok');

        dialog.querySelector('.confirm-cancel').addEventListener('click', function () {
            pendingOk = null;
            dialog.close();
        });

        okBtn.addEventListener('click', function () {
            var action = pendingOk;
            pendingOk = null;
            dialog.close();
            if (action) action();
        });

        // Click on the dimmed backdrop closes the dialog
        dialog.addEventListener('click', function (e) {
            if (e.target === dialog) {
                pendingOk = null;
                dialog.close();
            }
        });
    }

    function ask(opts) {
        // Older browsers without <dialog>: plain confirm box
        if (typeof HTMLDialogElement === 'undefined') {
            if (window.confirm(opts.text)) opts.onOk();
            return;
        }
        build();
        titleEl.textContent = opts.title;
        textEl.textContent = opts.text;
        okBtn.textContent = opts.okLabel;
        pendingOk = opts.onOk;
        dialog.showModal();
    }

    // Log out links
    document.querySelectorAll('a[href="logout.php"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            ask({
                title: 'Log out?',
                text: 'Are you sure you want to log out of FaciliFix?',
                okLabel: 'Log Out',
                onOk: function () { window.location.href = 'logout.php'; }
            });
        });
    });

    // Delete (or any other) buttons that need confirming
    document.querySelectorAll('button[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            ask({
                title: btn.dataset.confirmTitle || 'Are you sure?',
                text: btn.dataset.confirm,
                okLabel: btn.dataset.confirmOk || 'Confirm',
                onOk: function () { btn.form.submit(); }
            });
        });
    });
})();