(function () {
    const PER_PAGE = 5;

    const rows = Array.from(document.querySelectorAll('.eq-row'));
    if (!rows.length) return;

    const searchInput = document.getElementById('staffSearch');
    const pagination = document.getElementById('pagination');
    const noResults = document.getElementById('noResults');
    const state = { query: '', page: 1 };

    const ARROW_LEFT  = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
    const ARROW_RIGHT = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';

    function render() {
        const q = state.query.trim().toLowerCase();
        const filtered = rows.filter(function (row) {
            return !q || row.dataset.search.includes(q);
        });

        const pages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
        state.page = Math.min(state.page, pages);
        const start = (state.page - 1) * PER_PAGE;

        rows.forEach(function (row) { row.hidden = true; });
        filtered.slice(start, start + PER_PAGE).forEach(function (row) { row.hidden = false; });

        noResults.hidden = filtered.length > 0;
        buildPagination(pages);
    }

    function makeButton(className, html, label) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = className;
        btn.innerHTML = html;
        btn.setAttribute('aria-label', label);
        return btn;
    }

    function buildPagination(pages) {
        pagination.innerHTML = '';
        if (pages <= 1) {
            pagination.hidden = true;
            return;
        }
        pagination.hidden = false;

        const prev = makeButton('page-arrow', ARROW_LEFT, 'Previous page');
        prev.disabled = state.page === 1;
        prev.addEventListener('click', function () { state.page--; render(); });
        pagination.appendChild(prev);

        for (let i = 1; i <= pages; i++) {
            const btn = makeButton('page-button' + (i === state.page ? ' active' : ''), String(i), 'Page ' + i);
            if (i === state.page) btn.setAttribute('aria-current', 'page');
            btn.addEventListener('click', function () { state.page = i; render(); });
            pagination.appendChild(btn);
        }

        const next = makeButton('page-arrow', ARROW_RIGHT, 'Next page');
        next.disabled = state.page === pages;
        next.addEventListener('click', function () { state.page++; render(); });
        pagination.appendChild(next);
    }

    searchInput.addEventListener('input', function () {
        state.query = this.value;
        state.page = 1;
        render();
    });

    render();
})();
