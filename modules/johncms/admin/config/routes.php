<?php

declare(strict_types=1);

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Application\Controllers\Access\AuthLogController;
use Johncms\Modules\Admin\Application\Controllers\Access\ExternalProvidersController;
use Johncms\Modules\Admin\Application\Controllers\Access\RolesController;
use Johncms\Modules\Admin\Application\Controllers\Access\UserRolesController;
use Johncms\Modules\Admin\Application\Controllers\DashboardController;
use Johncms\Modules\Admin\Application\Controllers\Forum\ForumDashboardController;
use Johncms\Modules\Admin\Application\Controllers\Forum\ForumSettingsController;
use Johncms\Modules\Admin\Application\Controllers\Forum\ForumStructureController;
use Johncms\Modules\Admin\Application\Controllers\Forum\HiddenPostsController;
use Johncms\Modules\Admin\Application\Controllers\Forum\HiddenTopicsController;
use Johncms\Modules\Admin\Application\Controllers\Ip\IpBanController;
use Johncms\Modules\Admin\Application\Controllers\Ip\IpSearchController;
use Johncms\Modules\Admin\Application\Controllers\Ip\IpWhoisController;
use Johncms\Modules\Admin\Application\Controllers\Languages\LanguagesController;
use Johncms\Modules\Admin\Application\Controllers\Settings\AdsController;
use Johncms\Modules\Admin\Application\Controllers\Settings\AntifloodSettingsController;
use Johncms\Modules\Admin\Application\Controllers\Settings\CountersController;
use Johncms\Modules\Admin\Application\Controllers\Settings\CaptchaSettingsController;
use Johncms\Modules\Admin\Application\Controllers\Settings\MailSettingsController;
use Johncms\Modules\Admin\Application\Controllers\Settings\SystemSettingsController;
use Johncms\Modules\Admin\Application\Controllers\System\EmoticonsController;
use Johncms\Modules\Admin\Application\Controllers\System\FileIntegrityController;
use Johncms\Modules\Admin\Application\Controllers\System\MaintenanceTasksController;
use Johncms\Modules\Admin\Application\Controllers\System\SystemCheckController;
use Johncms\Modules\Admin\Application\Controllers\Users\AmnestyController;
use Johncms\Modules\Admin\Application\Controllers\Users\BanListController;
use Johncms\Modules\Admin\Application\Controllers\Users\DeleteUserController;
use Johncms\Modules\Admin\Application\Controllers\Users\KarmaController;
use Johncms\Modules\Admin\Application\Controllers\Users\RegistrationModerationController;
use Johncms\Modules\Admin\Application\Controllers\Users\StaffListController;
use Johncms\Modules\Admin\Application\Controllers\Users\UserCleanupController;
use Johncms\Modules\Admin\Application\Controllers\Users\UserListController;
use Johncms\Modules\Admin\Application\Controllers\Users\UsersController;
use Johncms\Modules\Admin\Application\Middlewares\AdminAccessMiddleware;
use Johncms\Modules\Admin\Application\Middlewares\SuperAdminAccessMiddleware;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Public route: the admin login screen (no access guard).
    $router->map(['GET', 'POST'], '/admin/login', [UsersController::class, 'login'])->name('admin.login');

    $router->map(['GET', 'POST'], '/admin/system_check', [SystemCheckController::class, 'index'])
        ->name('admin.system_check')
        ->permission(AdminPermissions::SYSTEM_CHECK);

    // Routes migrated to the new architecture. The whole group is behind AdminAccessMiddleware,
    // which asks admin.access; screens that need more than that add their own gate.
    // Static segments are registered before the legacy catch-all below so they
    // win on first match (plain UrlMatcher, registration order).
    $adminGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/admin', DashboardController::class)->name('admin.index');
        $r->get('/admin/staff', StaffListController::class)->name('admin.staff');
        $r->get('/admin/users', UserListController::class)->name('admin.users');
        $r->get('/admin/users/cleanup', [UserCleanupController::class, 'index'])->name('admin.users.cleanup');
        $r->post('/admin/users/cleanup', [UserCleanupController::class, 'clean'])->name('admin.users.cleanup.run');
        $r->get('/admin/users/{sort}', UserListController::class)
            ->name('admin.users.sort')
            ->requirements(['sort' => 'by-nick|by-ip']);
        // Who may open these is decided by the admin.roles.manage permission rather than by the
        // group: an administrator holds it, and a role standing above the visitor's own is
        // listed but not opened.
        $r->get('/admin/roles', [RolesController::class, 'index'])->name('admin.roles');
        $r->get('/admin/roles/new', [RolesController::class, 'newForm'])->name('admin.roles.new');
        $r->post('/admin/roles/new', [RolesController::class, 'store'])->name('admin.roles.store');
        $r->get('/admin/roles/{id:number}/edit', [RolesController::class, 'editForm'])->name('admin.roles.edit');
        $r->post('/admin/roles/{id:number}/edit', [RolesController::class, 'update'])->name('admin.roles.update');
        $r->get('/admin/roles/{id:number}/delete', [RolesController::class, 'deleteConfirm'])->name('admin.roles.delete_confirm');
        $r->post('/admin/roles/{id:number}/delete', [RolesController::class, 'delete'])->name('admin.roles.delete');
        // Reading the trail is a permission of its own: it names who did what, which is more than
        // "may open the panel" is meant to grant.
        $r->get('/admin/auth/providers', [ExternalProvidersController::class, 'index'])
            ->name('admin.auth.providers')
            ->permission(CorePermissions::ADMIN_SETTINGS_MANAGE);
        $r->post('/admin/auth/providers', [ExternalProvidersController::class, 'save'])
            ->name('admin.auth.providers.save')
            ->permission(CorePermissions::ADMIN_SETTINGS_MANAGE);
        $r->get('/admin/auth-log', AuthLogController::class)
            ->name('admin.auth_log')
            ->permission(AdminPermissions::AUTH_LOG_VIEW);
        $r->get('/admin/users/{id:number}/roles', [UserRolesController::class, 'form'])->name('admin.users.roles');
        $r->post('/admin/users/{id:number}/roles', [UserRolesController::class, 'save'])->name('admin.users.roles.save');
        $r->get('/admin/ip-whois', IpWhoisController::class)->name('admin.ip_whois');
        $r->get('/admin/ip-search', IpSearchController::class)->name('admin.ip_search');
        $r->get('/admin/ip-search/{mode}', IpSearchController::class)
            ->name('admin.ip_search.mode')
            ->requirements(['mode' => 'history']);
        $r->get('/admin/bans', BanListController::class)->name('admin.bans');
        $r->get('/admin/bans/{sort}', BanListController::class)
            ->name('admin.bans.sort')
            ->requirements(['sort' => 'by-violations']);
        $r->get('/admin/antiflood', [AntifloodSettingsController::class, 'form'])->name('admin.antiflood');
        $r->post('/admin/antiflood', [AntifloodSettingsController::class, 'save'])->name('admin.antiflood.save');
        $r->get('/admin/registrations', [RegistrationModerationController::class, 'index'])->name('admin.registrations');
        $r->post('/admin/registrations/approve', [RegistrationModerationController::class, 'approve'])->name('admin.registrations.approve');
        $r->post('/admin/registrations/approve-all', [RegistrationModerationController::class, 'approveAll'])->name('admin.registrations.approve_all');
        $r->post('/admin/registrations/delete', [RegistrationModerationController::class, 'delete'])->name('admin.registrations.delete');
        $r->post('/admin/registrations/delete-all', [RegistrationModerationController::class, 'deleteAll'])->name('admin.registrations.delete_all');
        $r->post('/admin/registrations/delete-by-ip', [RegistrationModerationController::class, 'deleteByIp'])->name('admin.registrations.delete_by_ip');
        $r->get('/admin/ads', [AdsController::class, 'index'])->name('admin.ads');
        $r->get('/admin/ads/new', [AdsController::class, 'newForm'])->name('admin.ads.new');
        $r->post('/admin/ads', [AdsController::class, 'store'])->name('admin.ads.store');
        $r->get('/admin/ads/clear', [AdsController::class, 'clearConfirm'])->name('admin.ads.clear_confirm');
        $r->post('/admin/ads/clear', [AdsController::class, 'clear'])->name('admin.ads.clear');
        $r->get('/admin/ads/{id:number}/edit', [AdsController::class, 'editForm'])->name('admin.ads.edit');
        $r->post('/admin/ads/{id:number}/up', [AdsController::class, 'up'])->name('admin.ads.up');
        $r->post('/admin/ads/{id:number}/down', [AdsController::class, 'down'])->name('admin.ads.down');
        $r->post('/admin/ads/{id:number}/toggle', [AdsController::class, 'toggle'])->name('admin.ads.toggle');
        $r->get('/admin/ads/{id:number}/delete', [AdsController::class, 'deleteConfirm'])->name('admin.ads.delete_confirm');
        $r->post('/admin/ads/{id:number}/delete', [AdsController::class, 'delete'])->name('admin.ads.delete');
        $r->get('/admin/forum', ForumDashboardController::class)->name('admin.forum');
        $r->get('/admin/forum/structure', [ForumStructureController::class, 'structure'])->name('admin.forum.structure');
        $r->get('/admin/forum/structure/new', [ForumStructureController::class, 'addForm'])->name('admin.forum.structure.new');
        $r->post('/admin/forum/structure/new', [ForumStructureController::class, 'add'])->name('admin.forum.structure.add');
        $r->get('/admin/forum/structure/{id:number}/edit', [ForumStructureController::class, 'editForm'])->name('admin.forum.structure.edit');
        $r->post('/admin/forum/structure/{id:number}/edit', [ForumStructureController::class, 'edit'])->name('admin.forum.structure.update');
        $r->get('/admin/forum/structure/{id:number}/delete', [ForumStructureController::class, 'deleteConfirm'])->name('admin.forum.structure.delete_confirm');
        $r->post('/admin/forum/structure/{id:number}/delete', [ForumStructureController::class, 'delete'])->name('admin.forum.structure.delete');
        $r->get('/admin/forum/hidden-topics', [HiddenTopicsController::class, 'index'])->name('admin.forum.hidden_topics');
        $r->post('/admin/forum/hidden-topics/delete', [HiddenTopicsController::class, 'deleteAll'])->name('admin.forum.hidden_topics.delete');
        $r->get('/admin/forum/hidden-posts', [HiddenPostsController::class, 'index'])->name('admin.forum.hidden_posts');
        $r->post('/admin/forum/hidden-posts/delete', [HiddenPostsController::class, 'deleteAll'])->name('admin.forum.hidden_posts.delete');
        $r->get('/admin/emoticons', [EmoticonsController::class, 'index'])->name('admin.emoticons');
        $r->post('/admin/emoticons', [EmoticonsController::class, 'rebuild'])->name('admin.emoticons.rebuild');

        // Everything that changes the site as a whole. Group middleware does not propagate
        // into nested groups, so SuperAdminAccessMiddleware is self-contained and asks for
        // admin.settings.manage on its own.
        $superGroup = $r->group('', function (RouteCollection $sr): void {
            $sr->get('/admin/settings', [SystemSettingsController::class, 'form'])->name('admin.settings');
            $sr->post('/admin/settings', [SystemSettingsController::class, 'save'])->name('admin.settings.save');
            $sr->get('/admin/settings/mail', [MailSettingsController::class, 'form'])->name('admin.settings.mail');
            $sr->post('/admin/settings/mail', [MailSettingsController::class, 'save'])->name('admin.settings.mail.save');
            $sr->post('/admin/settings/mail/test', [MailSettingsController::class, 'test'])->name('admin.settings.mail.test');
            $sr->get('/admin/settings/captcha', [CaptchaSettingsController::class, 'form'])->name('admin.settings.captcha');
            $sr->post('/admin/settings/captcha', [CaptchaSettingsController::class, 'save'])
                ->name('admin.settings.captcha.save');

            $sr->get('/admin/ip-bans', [IpBanController::class, 'index'])->name('admin.ip_bans');
            $sr->get('/admin/ip-bans/new', [IpBanController::class, 'newForm'])->name('admin.ip_bans.new');
            $sr->post('/admin/ip-bans/new', [IpBanController::class, 'prepare'])->name('admin.ip_bans.prepare');
            $sr->post('/admin/ip-bans', [IpBanController::class, 'store'])->name('admin.ip_bans.store');
            $sr->get('/admin/ip-bans/search', [IpBanController::class, 'searchForm'])->name('admin.ip_bans.search_form');
            $sr->post('/admin/ip-bans/search', [IpBanController::class, 'search'])->name('admin.ip_bans.search');
            $sr->get('/admin/ip-bans/clear', [IpBanController::class, 'clearConfirm'])->name('admin.ip_bans.clear_confirm');
            $sr->post('/admin/ip-bans/clear', [IpBanController::class, 'clear'])->name('admin.ip_bans.clear');
            $sr->get('/admin/ip-bans/{id:number}', [IpBanController::class, 'detail'])->name('admin.ip_bans.detail');
            $sr->post('/admin/ip-bans/{id:number}/delete', [IpBanController::class, 'delete'])->name('admin.ip_bans.delete');

            $sr->get('/admin/counters', [CountersController::class, 'index'])->name('admin.counters');
            $sr->get('/admin/counters/new', [CountersController::class, 'newForm'])->name('admin.counters.new');
            $sr->post('/admin/counters/preview', [CountersController::class, 'preview'])->name('admin.counters.preview');
            $sr->post('/admin/counters', [CountersController::class, 'store'])->name('admin.counters.store');
            $sr->get('/admin/counters/{id:number}', [CountersController::class, 'view'])->name('admin.counters.view');
            $sr->get('/admin/counters/{id:number}/edit', [CountersController::class, 'editForm'])->name('admin.counters.edit');
            $sr->post('/admin/counters/{id:number}/toggle', [CountersController::class, 'toggle'])->name('admin.counters.toggle');
            $sr->post('/admin/counters/{id:number}/up', [CountersController::class, 'up'])->name('admin.counters.up');
            $sr->post('/admin/counters/{id:number}/down', [CountersController::class, 'down'])->name('admin.counters.down');
            $sr->get('/admin/counters/{id:number}/delete', [CountersController::class, 'deleteConfirm'])->name('admin.counters.delete_confirm');
            $sr->post('/admin/counters/{id:number}/delete', [CountersController::class, 'delete'])->name('admin.counters.delete');

            $sr->get('/admin/maintenance', [MaintenanceTasksController::class, 'index'])->name('admin.maintenance');
            $sr->post('/admin/maintenance/run', [MaintenanceTasksController::class, 'run'])->name('admin.maintenance.run');

            $sr->get('/admin/file-integrity', [FileIntegrityController::class, 'index'])->name('admin.file_integrity');
            $sr->get('/admin/file-integrity/scan', [FileIntegrityController::class, 'scan'])->name('admin.file_integrity.scan');
            $sr->get('/admin/file-integrity/snapshot', [FileIntegrityController::class, 'snapshotConfirm'])->name('admin.file_integrity.snapshot');
            $sr->post('/admin/file-integrity/snapshot', [FileIntegrityController::class, 'createSnapshot'])->name('admin.file_integrity.snapshot.create');

            $sr->get('/admin/forum/settings', [ForumSettingsController::class, 'form'])->name('admin.forum.settings');
            $sr->post('/admin/forum/settings', [ForumSettingsController::class, 'save'])->name('admin.forum.settings.save');

            $sr->get('/admin/karma', [KarmaController::class, 'index'])->name('admin.karma');
            $sr->post('/admin/karma', [KarmaController::class, 'save'])->name('admin.karma.save');
            $sr->get('/admin/karma/clear', [KarmaController::class, 'clearConfirm'])->name('admin.karma.clear');
            $sr->post('/admin/karma/reset', [KarmaController::class, 'reset'])->name('admin.karma.reset');

            $sr->get('/admin/bans/amnesty', [AmnestyController::class, 'form'])->name('admin.bans.amnesty');
            $sr->post('/admin/bans/amnesty', [AmnestyController::class, 'apply'])->name('admin.bans.amnesty.apply');

            $sr->get('/admin/users/{id:number}/delete', [DeleteUserController::class, 'index'])->name('admin.users.delete');
            $sr->post('/admin/users/{id:number}/delete', [DeleteUserController::class, 'delete'])->name('admin.users.delete.run');

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
};
