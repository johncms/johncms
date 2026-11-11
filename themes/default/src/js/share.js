import { announce, toast } from './toast';

// The "Share" button of @theme/components/share.twig. A touch screen with the Web Share API gets
// the share sheet of the system; everywhere else the popover opens, and "Copy link" appears in it
// when the clipboard can be written.
const COPIED_FOR = 1500;
const shareSheet = typeof navigator.share === 'function' && window.matchMedia('(pointer: coarse)').matches;
const clipboard = typeof navigator.clipboard?.writeText === 'function';

for (const share of document.querySelectorAll('[data-share]')) {
    const url = new URL(share.dataset.shareUrl, window.location.href).href;

    if (shareSheet) {
        share.querySelector('summary').addEventListener('click', event => {
            event.preventDefault();
            navigator.share({title: share.dataset.shareTitle, url}).catch(error => {
                // Closing the sheet is not a failure; anything else falls back to the popover.
                if (error.name !== 'AbortError') share.open = true;
            });
        });
    }

    if (!clipboard) continue;
    for (const item of share.querySelectorAll('[data-share-copy-item]')) item.hidden = false;
    const button = share.querySelector('[data-share-copy]');
    const label = button.querySelector('[data-share-copy-label]');
    const text = label.textContent;
    let restore;

    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(url);
        } catch (error) {
            console.error('Could not copy the link.', error);
            share.open = false;
            toast(share.dataset.shareError, {type: 'error'});
            return;
        }
        label.textContent = share.dataset.shareCopied;
        announce(share.dataset.shareCopied);
        clearTimeout(restore);
        restore = setTimeout(() => {
            share.open = false;
            label.textContent = text;
        }, COPIED_FOR);
    });
}
