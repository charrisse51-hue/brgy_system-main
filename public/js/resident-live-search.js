document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('residentFilters');
    const split = document.getElementById('resSplitContainer');
    if (!form || !split) return;
    const search = form.elements.namedItem('search');
    const sitio = form.elements.namedItem('sitio');
    const feedback = document.getElementById('residentSearchFeedback');
    let timer;
    let controller;
    let revision = 0;

    function filterUrl() {
        const url = new URL(form.action);
        for (const [key, value] of new FormData(form)) {
            if (String(value).trim()) url.searchParams.set(key, String(value).trim());
        }
        return url;
    }

    async function loadResults(url, version) {
        controller = new AbortController();
        const signal = controller.signal;
        split.setAttribute('aria-busy', 'true');
        feedback.hidden = true;
        try {
            const response = await fetch(url, {
                signal,
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!response.ok) throw new Error('Could not load resident records.');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (version !== revision || signal.aborted) return;
            const selectors = ['.res-list-table-wrap', '.res-list-footer', '.res-count-badge', '.res-profile-panel'];
            const replacements = selectors.map(selector => [split.querySelector(selector), doc.querySelector(selector)]);
            if (replacements.some(([current, next]) => !current || !next)) {
                throw new Error('Could not load resident records. Please refresh the page and sign in if needed.');
            }
            const serverError = doc.querySelector('#residentFilters .filter-error:not(#residentSearchFeedback)');
            if (serverError) throw new Error(serverError.textContent.trim());
            replacements.forEach(([current, next]) => current.replaceWith(document.importNode(next, true)));

            // Apply the same responsive table semantics to the newly loaded rows.
            const table = split.querySelector('.res-list-table');
            table.classList.add('ui-record-table');
            table.setAttribute('role', 'table');
            table.parentElement.classList.add('ui-record-region');
            const headers = [...table.tHead.rows[0].cells].map(cell => cell.textContent.trim());
            table.querySelectorAll('thead, tbody').forEach(group => group.setAttribute('role', 'rowgroup'));
            table.querySelectorAll('tr').forEach(row => row.setAttribute('role', 'row'));
            [...table.tHead.rows[0].cells].forEach(cell => {
                cell.scope = 'col';
                cell.setAttribute('role', 'columnheader');
            });
            [...table.tBodies[0].rows].forEach(row => {
                let column = 0;
                [...row.cells].forEach(cell => {
                    cell.setAttribute('role', 'cell');
                    if (cell.colSpan === 1) cell.dataset.label = headers[column];
                    column += cell.colSpan;
                });
            });
            // Searches return to the list on mobile; choosing a row opens its details.
            split.classList.remove('res-show-details');
            history.replaceState(null, '', url);
            document.dispatchEvent(new CustomEvent('resident-results-updated'));
            const count = split.querySelectorAll('.res-row').length;
            feedback.textContent = count ? `${count} resident${count === 1 ? '' : 's'} displayed.` : 'No matching residents found.';
            feedback.classList.remove('filter-error');
            feedback.hidden = false;
        } catch (error) {
            if (error.name === 'AbortError' || version !== revision) return;
            feedback.classList.add('filter-error');
            feedback.textContent = error.message || 'Search failed. Please try again.';
            feedback.hidden = false;
        } finally {
            if (version === revision) split.removeAttribute('aria-busy');
        }
    }

    function schedule(url, delay = 0) {
        clearTimeout(timer);
        controller?.abort();
        const version = ++revision;
        timer = setTimeout(() => loadResults(url, version), delay);
    }
    search.addEventListener('input', () => schedule(filterUrl(), 300));
    sitio.addEventListener('change', () => schedule(filterUrl()));
    form.addEventListener('submit', event => {
        event.preventDefault();
        schedule(filterUrl());
    });
    split.addEventListener('click', event => {
        const link = event.target.closest('.res-list-footer a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        schedule(new URL(link.href));
    });
});
