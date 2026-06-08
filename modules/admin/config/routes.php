<?php

declare(strict_types=1);

use Johncms\Modules\Admin\Application\Controllers\DashboardController;
use Johncms\Modules\Admin\Application\Controllers\Ip\IpSearchController;
use Johncms\Modules\Admin\Application\Controllers\Languages\LanguagesController;
use Johncms\Modules\Admin\Application\Controllers\Ip\IpWhoisController;
use Johncms\Modules\Admin\Application\Controllers\Settings\AntifloodSettingsController;
use Johncms\Modules\Admin\Application\Controllers\Settings\ModulesAccessController;
use Johncms\Modules\Admin\Application\Controllers\Settings\SystemSettingsController;
use Johncms\Modules\Admin\Application\Controllers\System\EmoticonsController;
use Johncms\Modules\Admin\Application\Controllers\System\SystemCheckController;
use Johncms\Modules\Admin\Application\Controllers\Users\BanListController;
use Johncms\Modules\Admin\Application\Controllers\Users\RegistrationModerationController;
use Johncms\Modules\Admin\Application\Controllers\Users\StaffListController;
use Johncms\Modules\Admin\Application\Controllers\Users\UserListController;
use Johncms\Modules\Admin\Application\Controllers\Users\UsersController;
use Johncms\Modules\Admin\Application\Middlewares\AdminAccessMiddleware;
use Johncms\Modules\Admin\Application\Middlewares\SuperAdminAccessMiddleware;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    // Public route: the admin login screen (no access guard).
    $router->map(['GET', 'POST'], '/admin/login', [UsersController::class, 'login'])->name('admin.login');

    if ($user->rights >= 6 && $user->isValid()) {
        $router->map(['GET', 'POST'], '/admin/system_check', [SystemCheckController::class, 'index'])->name('admin.system_check');
    }

    // Routes migrated to the new architecture. The whole group requires an
    // administrator (rights >= 7); higher-privilege actions add their own guards.
    // Static segments are registered before the legacy catch-all below so they
    // win on first match (plain UrlMatcher, registration order).
    $adminGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/admin', DashboardController::class)->name('admin.index');
        $r->get('/admin/staff', StaffListController::class)->name('admin.staff');
        $r->get('/admin/users', UserListController::class)->name('admin.users');
        $r->get('/admin/users/{sort}', UserListController::class)
            ->name('admin.users.sort')
            ->requirements(['sort' => 'by-nick|by-ip']);
        $r->get('/admin/ip-whois', IpWhoisController::class)->name('admin.ip_whois');
        $r->get('/admin/ip-search', IpSearchController::class)->name('admin.ip_search');
        $r->get('/admin/ip-search/{mode}', IpSearchController::class)
            ->name('admin.ip_search.mode')
            ->requirements(['mode' => 'history']);
        $r->get('/admin/bans', BanListController::class)->name('admin.bans');
        $r->get('/admin/bans/{sort}', BanListController::class)
            ->name('admin.bans.sort')
            ->requirements(['sort' => 'by-violations']);
        $r->get('/admin/modules-access', [ModulesAccessController::class, 'form'])->name('admin.modules_access');
        $r->post('/admin/modules-access', [ModulesAccessController::class, 'save'])->name('admin.modules_access.save');
        $r->get('/admin/antiflood', [AntifloodSettingsController::class, 'form'])->name('admin.antiflood');
        $r->post('/admin/antiflood', [AntifloodSettingsController::class, 'save'])->name('admin.antiflood.save');
        $r->get('/admin/registrations', [RegistrationModerationController::class, 'index'])->name('admin.registrations');
        $r->post('/admin/registrations/approve', [RegistrationModerationController::class, 'approve'])->name('admin.registrations.approve');
        $r->post('/admin/registrations/approve-all', [RegistrationModerationController::class, 'approveAll'])->name('admin.registrations.approve_all');
        $r->post('/admin/registrations/delete', [RegistrationModerationController::class, 'delete'])->name('admin.registrations.delete');
        $r->post('/admin/registrations/delete-all', [RegistrationModerationController::class, 'deleteAll'])->name('admin.registrations.delete_all');
        $r->post('/admin/registrations/delete-by-ip', [RegistrationModerationController::class, 'deleteByIp'])->name('admin.registrations.delete_by_ip');
        $r->get('/admin/emoticons', [EmoticonsController::class, 'index'])->name('admin.emoticons');
        $r->post('/admin/emoticons', [EmoticonsController::class, 'rebuild'])->name('admin.emoticons.rebuild');

        // Higher-privilege actions (rights >= 9). Group middleware does not propagate
        // into nested groups, so SuperAdminAccessMiddleware is self-contained and
        // applies the stricter gate on its own (rights >= 9 implies rights >= 7).
        $superGroup = $r->group('', function (RouteCollection $sr): void {
            $sr->get('/admin/settings', [SystemSettingsController::class, 'form'])->name('admin.settings');
            $sr->post('/admin/settings', [SystemSettingsController::class, 'save'])->name('admin.settings.save');

            $sr->get('/admin/languages', [LanguagesController::class, 'index'])->name('admin.languages');
            $sr->post('/admin/languages', [LanguagesController::class, 'save'])->name('admin.languages.save');
            $sr->get('/admin/languages/manage', [LanguagesController::class, 'manage'])->name('admin.languages.manage');
            $sr->post('/admin/languages/install', [LanguagesController::class, 'install'])->name('admin.languages.install');
            $sr->post('/admin/languages/update', [LanguagesController::class, 'update'])->name('admin.languages.update');
            $sr->post('/admin/languages/delete', [LanguagesController::class, 'delete'])->name('admin.languages.delete');
        });
        $superGroup->addMiddleware(SuperAdminAccessMiddleware::class);
    });
    $adminGroup->addMiddleware(AdminAccessMiddleware::class);

    // Legacy query-param dispatcher for actions not yet migrated. Negative priority
    // keeps this catch-all last in the compiled collection, so every migrated route
    // (now and in the future) is matched before it — group routes are compiled after
    // top-level routes, so registration order alone is not enough.
    $router->map(['GET', 'POST'], '/admin/{action}', 'modules/admin/index.php')
        ->name('admin.legacy')
        ->priority(-100)
        ->defaults(['action' => null]);
};
