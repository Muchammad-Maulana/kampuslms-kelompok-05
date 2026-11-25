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

const assignmentList = document.querySelector('[data-assignment-list]');

if (assignmentList) {
    const cards = Array.from(assignmentList.querySelectorAll('[data-assignment-card]'));
    const searchInput = document.querySelector('[data-assignment-search]');
    const filterButtons = Array.from(document.querySelectorAll('[data-assignment-filter]'));
    const emptyMessage = document.querySelector('[data-assignment-empty]');
    let activeFilter = 'all';

    const updateAssignmentList = () => {
        const searchTerm = searchInput?.value.trim().toLocaleLowerCase() ?? '';
        let visibleCount = 0;

        cards.forEach((card) => {
            const tokens = card.dataset.filterTokens?.split(' ') ?? [];
            const matchesFilter = activeFilter === 'all' || tokens.includes(activeFilter);
            const matchesSearch = card.dataset.search?.includes(searchTerm) ?? false;
            const isVisible = matchesFilter && matchesSearch;

            card.hidden = !isVisible;
            visibleCount += isVisible ? 1 : 0;
        });

        if (emptyMessage) {
            emptyMessage.hidden = visibleCount > 0;
        }
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.assignmentFilter ?? 'all';

            filterButtons.forEach((filterButton) => {
                const isActive = filterButton === button;
                filterButton.setAttribute('aria-pressed', String(isActive));
                filterButton.classList.toggle('bg-blue-700', isActive);
                filterButton.classList.toggle('text-white', isActive);
                filterButton.classList.toggle('bg-white', !isActive);
                filterButton.classList.toggle('text-slate-600', !isActive);
            });

            updateAssignmentList();
        });
    });

    searchInput?.addEventListener('input', updateAssignmentList);
}
