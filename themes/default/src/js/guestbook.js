import {clearEditor} from './editor';

const guestbook = document.querySelector('#guestbook');
guestbook?.addEventListener('live-list:update', event => {
    const compose = guestbook.querySelector('[data-guestbook-form]');
    if (compose) compose.action = location.pathname + location.search;
    const form = event.detail.form;
    if (!form?.matches('[data-guestbook-form]')) return;
    // Remote widgets stay mounted; their one-use answers must be renewed after a POST.
    if (form.querySelector('.h-captcha') && window.hcaptcha) window.hcaptcha.reset();
    if (form.querySelector('.smart-captcha') && window.smartCaptcha) window.smartCaptcha.reset();
    if (!guestbook.querySelector('[data-guestbook-added]')) return;
    const field = form.querySelector('[data-editor]');
    if (field) clearEditor(field);
});
