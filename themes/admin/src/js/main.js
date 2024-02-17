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
