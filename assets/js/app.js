document.querySelectorAll('.confirm-delete').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        if (!window.confirm('Are you sure you want to delete this record?')) {
            e.preventDefault();
        }
    });
});
