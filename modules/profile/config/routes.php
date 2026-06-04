<?php

declare(strict_types=1);

use Johncms\Modules\Profile\Application\Controllers\AccountController;
use Johncms\Modules\Profile\Application\Controllers\ActivityController;
use Johncms\Modules\Profile\Application\Controllers\ChangePasswordController;
use Johncms\Modules\Profile\Application\Controllers\ConfirmNewEmailController;
use Johncms\Modules\Profile\Application\Controllers\EditProfileController;
use Johncms\Modules\Profile\Application\Controllers\GuestbookController;
use Johncms\Modules\Profile\Application\Controllers\IpHistoryController;
use Johncms\Modules\Profile\Application\Controllers\ProfileController;
use Johncms\Modules\Profile\Application\Controllers\ResetSettingsController;
use Johncms\Modules\Profile\Application\Controllers\SettingsController;
use Johncms\Modules\Profile\Application\Controllers\StatisticsController;
use Johncms\Modules\Profile\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    // Routes migrated to the new architecture (require an authenticated user)
    $profileGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/profile/account', AccountController::class)->name('profile.account');
        $r->get('/profile/settings', [SettingsController::class, 'general'])->name('profile.settings');
        $r->post('/profile/settings', [SettingsController::class, 'saveGeneral'])->name('profile.settings.save');
        $r->post('/profile/settings/reset', [SettingsController::class, 'resetGeneral'])->name('profile.settings.reset');
        $r->get('/profile/settings/forum', [SettingsController::class, 'forum'])->name('profile.settings.forum');
        $r->post('/profile/settings/forum', [SettingsController::class, 'saveForum'])->name('profile.settings.forum.save');
        $r->post('/profile/settings/forum/reset', [SettingsController::class, 'resetForum'])->name('profile.settings.forum.reset');
        $r->get('/profile/settings/mail', [SettingsController::class, 'mail'])->name('profile.settings.mail');
        $r->post('/profile/settings/mail', [SettingsController::class, 'saveMail'])->name('profile.settings.mail.save');
        $r->get('/profile/{id:number}/statistics', StatisticsController::class)->name('profile.statistics');
        $r->get('/profile/{id:number}/ip-history', IpHistoryController::class)->name('profile.ip-history');
        $r->get('/profile/{id:number}/password', [ChangePasswordController::class, 'form'])->name('profile.password');
        $r->post('/profile/{id:number}/password', [ChangePasswordController::class, 'change'])->name('profile.password.change');
        $r->map(['GET', 'POST'], '/profile/{id:number}/guestbook', GuestbookController::class)->name('profile.guestbook');
        $r->post('/profile/{id:number}/reset-settings', ResetSettingsController::class)->name('profile.reset-settings');
        $r->get('/profile/{id:number}/edit', [EditProfileController::class, 'form'])->name('profile.edit');
        $r->post('/profile/{id:number}/edit', [EditProfileController::class, 'save'])->name('profile.edit.save');
        $r->post('/profile/{id:number}/edit/delete-avatar', [EditProfileController::class, 'deleteAvatar'])->name('profile.edit.delete-avatar');
        $r->post('/profile/{id:number}/edit/delete-photo', [EditProfileController::class, 'deletePhoto'])->name('profile.edit.delete-photo');
        $r->get('/profile/{id:number}/activity', [ActivityController::class, 'messages'])->name('profile.activity');
        $r->get('/profile/{id:number}/activity/topics', [ActivityController::class, 'topics'])->name('profile.activity.topics');
        $r->get('/profile/{id:number}/activity/comments', [ActivityController::class, 'comments'])->name('profile.activity.comments');
        $r->get('/profile/{id:number}', ProfileController::class)->name('profile.view');
    });
    $profileGroup->addMiddleware(AuthorizedUserMiddleware::class);

    // Public routes (no authentication required, access is granted by the code from the email link)
    $router->get('/profile/confirm-email/{id:number}/{code}', ConfirmNewEmailController::class)->name('profile.confirm-email');

    // Legacy routes (gradually being migrated)
    $router->map(['GET', 'POST'], '/profile/skl.php', 'modules/profile/skl.php')->name('profile.skl');
    $router->map(['GET', 'POST'], '/profile', 'modules/profile/index.php')->name('profile.index');
};
