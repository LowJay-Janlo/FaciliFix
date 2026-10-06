/* ============================================
   FaciliFix shared layout script
   - Sidebar show / hide
   - Log out confirmation (works on any page with a link to logout.php)
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

    // ---------- Log out confirmation ----------
    var logoutLinks = document.querySelectorAll('a[href="logout.php"]');
    if (!logoutLinks.length) return;

    var dialog = document.createElement('dialog');
    dialog.className = 'confirm-dialog';
    dialog.setAttribute('aria-labelledby', 'confirmTitle');
    dialog.innerHTML =
        '<h2 id="confirmTitle">Log out?</h2>' +
        '<p>Are you sure you want to log out of FaciliFix?</p>' +
        '<div class="confirm-actions">' +
            '<button type="button" class="confirm-cancel" autofocus>Cancel</button>' +
            '<a href="logout.php" class="confirm-ok">Log Out</a>' +
        '</div>';
    document.body.appendChild(dialog);

    dialog.querySelector('.confirm-cancel').addEventListener('click', function () {
        dialog.close();
    });

    // Click on the dimmed backdrop closes the dialog
    dialog.addEventListener('click', function (e) {
        if (e.target === dialog) dialog.close();
    });

    logoutLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            // Older browsers without <dialog>: fall back to a plain confirm box
            if (typeof dialog.showModal !== 'function') {
                if (!window.confirm('Are you sure you want to log out?')) e.preventDefault();
                return;
            }
            e.preventDefault();
            dialog.showModal();
        });
    });
})();
