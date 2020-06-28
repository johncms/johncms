/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

$(function () {
    const scroll_button = $('.to-top');

    if ($(document).height() > $(window).height() && $(this).scrollTop() < 50) {
        scroll_button.addClass('to-bottom').removeClass('to-top_hidden');
    }

    $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
            scroll_button.removeClass('to-bottom');
            scroll_button.addClass('to-header');
        } else {
            scroll_button.addClass('to-bottom');
            scroll_button.removeClass('to-header');
        }
    });

    $(".to-top").click(function (event) {
        event.preventDefault();
        if ($(this).hasClass('to-header')) {
            $('body,html').animate({scrollTop: 0}, 800);
        } else {
            $('body,html').animate({scrollTop: $(document).height()}, 800);
        }
    });
});

$(document).ready(function () {
    if (typeof wysibb_input != "undefined") {
        $(wysibb_input).wysibb(wysibb_settings);
    }
})

var sidebar = new StickySidebar('.sidebar', {
    topSpacing: 0,
    bottomSpacing: 20,
    containerSelector: '.page_layout',
    innerWrapperSelector: '.sidebar__inner',
    minWidth: 992,
});
