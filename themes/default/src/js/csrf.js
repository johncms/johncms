/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * The CSRF token of the page, published as a meta tag by the layout.
 *
 * The pipeline rejects every unsafe request that carries neither the csrf_token field nor this
 * header, so anything posting without a form — axios, and the upload adapter of CKEditor, which
 * sends its own XHR and never sees the axios defaults — has to send it explicitly.
 */
export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function csrfHeaders() {
    return { 'X-CSRF-Token': csrfToken() };
}
