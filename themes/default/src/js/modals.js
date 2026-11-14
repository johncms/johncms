import { Modal } from 'bootstrap';

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

$(document).on('click', '.select_language', function (event) {
  event.preventDefault();
  let select_language_form = $('form[name="select_language"]');

  $.ajax({
    type: "POST",
    url: select_language_form.attr('action'),
    dataType: "html",
    data: select_language_form.serialize(),
    success: function (html) {
      document.querySelectorAll('.ajax_modal').forEach(function (el) {
        Modal.getInstance(el)?.hide();
      });
      document.location.href = document.location.href;
    }
  });
});
