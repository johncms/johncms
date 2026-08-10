/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * Legacy scripts, templates and Vue components reference these libraries as
 * bare globals. They live in a module of their own because the imports of a module
 * are all evaluated before its body: libraries that look for window.jQuery while
 * they load, such as Bootstrap and flatpickr, must be imported after this one.
 */
import jquery from 'jquery';
import axios from 'axios';
import lodash from 'lodash';

window.$ = window.jQuery = jquery;
window.axios = axios;
window._ = lodash;

/**
 * Prism highlights automatically on load, which it does by inspecting
 * document.currentScript. That is null inside an ES module and makes the bundled
 * build throw, so it is switched to manual mode and ./app highlights explicitly.
 */
window.Prism = { manual: true };

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * The CSRF middleware accepts the token either as a csrf_token body field or as this
 * header. Requests with a JSON body have no form field to carry it, so every axios
 * call sends the token from the meta tag of the layout.
 */
const csrfToken = document.querySelector('meta[name="csrf-token"]');

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-Token'] = csrfToken.content;
}
