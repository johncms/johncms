/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

$(function () {
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.to-top').removeClass('to-top_hidden');
        } else {
            $('.to-top').addClass('to-top_hidden');
        }
    });

    $('.to-top').click(function (event) {
        event.preventDefault();
        $('body,html').animate({scrollTop: 0}, 800);
    });
});
