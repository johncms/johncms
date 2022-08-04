/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
  window.Popper = require('popper.js').default;
  window.$ = window.jQuery = require('jquery');
  window.axios = require('axios');
  window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

  let token = document.head.querySelector('meta[name="csrf-token"]');
  if (token) {
    window.axios.defaults.headers.common['X-CSRF-Token'] = token.content;
  } else {
    console.error('CSRF token not found!');
  }

  var _ = require('lodash');

  require('bootstrap');
} catch (e) {
}


