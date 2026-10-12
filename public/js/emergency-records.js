document.querySelectorAll('[data-close]').forEach(button => {
    button.addEventListener('click', () => button.closest('dialog').close());
});
document.querySelectorAll('.record-view[data-record]').forEach(button => {
    button.addEventListener('click', () => {
        const record = JSON.parse(button.dataset.record);
        document.getElementById('viewRecordTitle').textContent = record.title;
        document.getElementById('viewRecordLevel').textContent = `#EMG-${String(record.id).padStart(4, '0')} · ${record.level}`;
        document.getElementById('viewRecordMessage').textContent = record.message;
        document.getElementById('viewRecordDialog').showModal();
    });
});
