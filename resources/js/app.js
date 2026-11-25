import './bootstrap';

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const dismissButton = event.target.closest('[data-dismiss-flash]');
    dismissButton?.closest('[data-flash-message]')?.remove();
});

document.querySelectorAll('[data-flash-message][role="status"]').forEach((message) => {
    window.setTimeout(() => message.remove(), 7000);
});
