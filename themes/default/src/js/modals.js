/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

function getSpinner() {
    return '<div class="text-center p-5"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>';
}

$(function () {
    let ajax_modal = $('.ajax_modal');

    ajax_modal.on('show.bs.modal', function (event) {
        $('.ajax_modal .modal-content').html(getSpinner());
    });

    ajax_modal.on('shown.bs.modal', function (event) {
        let button = $(event.relatedTarget);
        let params = button.data();
        $.ajax({
            type: "POST",
            url: params.url,
            dataType: "html",
            data: params,
            success: function (html) {
                $('.ajax_modal .modal-content').html(html);
            }
        });
    });
});

$(document).on('click', '.select_language', function (event) {
    event.preventDefault();
    let select_language_form = $('form[name="select_language"]');

    $.ajax({
        type: "POST",
        url: select_language_form.attr('action'),
        dataType: "html",
        data: select_language_form.serialize(),
        success: function (html) {
            $('.ajax_modal').modal('hide');
            document.location.href = document.location.href;
        }
    });
});
