$(document)
    .on('click', '.navbar-toggler', function () {
        toggle_menu();
    })
    .on('click', '.sidebar_opened .content-container', function () {
        var body = $('body');
        if (body.hasClass('sidebar_opened')) {
            toggle_menu();
        }
    });

// Открытие/закрытие меню для мобильной версии
function toggle_menu() {
    var body = $('body');
    if (body.hasClass('sidebar_opened')) {
        body.removeClass('sidebar_opened');
        setTimeout(function () {
            $('.top_nav .navbar-toggle').removeClass('toggled');
        }, 500);

    } else {
        body.addClass('sidebar_opened');
        setTimeout(function () {
            $('.top_nav .navbar-toggle').addClass('toggled');
        }, 500);
    }
}
