$(document)
        .on('click', '.navbar-toggler, .show_menu_btn', function () {
            toggle_menu();
        })
        .on('click', '.sidebar_opened .overlay', function () {
            var body = $('body');
            if (body.hasClass('sidebar_opened')) {
                toggle_menu();
            }
        });

// Открытие/закрытие меню для мобильной версии
function toggle_menu()
{
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
