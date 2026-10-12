document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('incidentLogbookModal');
    if (modal) {
        const close = modal.querySelector('.logbook-close');
        close.focus({ preventScroll: true });
        modal.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.preventDefault(); close.click(); }
            if (event.key !== 'Tab') return;
            const controls = [...modal.querySelectorAll('a[href], button, input, select, textarea')].filter(control => !control.disabled && control.getClientRects().length);
            const first = controls[0], last = controls.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        });
    }
    const container = document.getElementById('logbook-persons');
    const add = document.getElementById('add-logbook-person');
    let next = container.children.length;
    const template = container.firstElementChild.cloneNode(true);
    add.addEventListener('click', () => {
        if (container.children.length >= 50) return;
        const person = template.cloneNode(true);
        person.querySelectorAll('input').forEach(input => {
            input.name = input.name.replace(/persons\[\d+\]/, `persons[${next}]`);
            input.value = '';
        });
        next++;
        container.append(person);
        person.querySelector('input').focus();
        add.disabled = container.children.length >= 50;
    });
    container.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-person]');
        if (!button) return;
        if (container.children.length === 1) {
            button.closest('.logbook-person').querySelectorAll('input').forEach(input => { input.value = ''; });
        } else button.closest('.logbook-person').remove();
        add.disabled = false;
    });
});
