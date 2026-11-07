// Copy the canonical article address; keep feedback beside the action.
for (const share of document.querySelectorAll('[data-news-share]')) {
    if (!navigator.clipboard?.writeText) continue;
    const button = share.querySelector('[data-news-copy]');
    const status = share.querySelector('[role="status"]');
    button.hidden = false;
    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            await navigator.clipboard.writeText(new URL(share.dataset.url, window.location.href).href);
            status.classList.remove('text-danger');
            status.textContent = share.dataset.copied;
        } catch (error) {
            status.classList.add('text-danger');
            status.textContent = share.dataset.error;
            console.error('Could not copy the article link.', error);
        } finally {
            button.disabled = false;
        }
    });
}
