/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

import axios from 'axios'
import {Tooltip} from "bootstrap";
import $ from 'jquery';
window.$ = window.jQuery = $;

try {
  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
  let token = document.head.querySelector('meta[name="csrf-token"]');
  if (token) {
    axios.defaults.headers.common['X-CSRF-Token'] = token.content;
  }

  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new Tooltip(tooltipTriggerEl)
  });
} catch (e) {
  console.log(e);
}


