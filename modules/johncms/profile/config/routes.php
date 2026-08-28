<?php

declare(strict_types=1);

use Johncms\Modules\Profile\Application\Controllers\AccountController;
use Johncms\Modules\Profile\Application\Controllers\ActivityController;
use Johncms\Modules\Profile\Application\Controllers\AvatarController;
use Johncms\Modules\Profile\Application\Controllers\BanController;
use Johncms\Modules\Profile\Application\Controllers\ChangePasswordController;
use Johncms\Modules\Profile\Application\Controllers\EditProfileController;
use Johncms\Modules\Profile\Application\Controllers\GuestbookController;
use Johncms\Modules\Profile\Application\Controllers\IpHistoryController;
use Johncms\Modules\Profile\Application\Controllers\KarmaController;
use Johncms\Modules\Profile\Application\Controllers\PhotoController;
use Johncms\Modules\Profile\Application\Controllers\ProfileController;
use Johncms\Modules\Profile\Application\Controllers\ResetSettingsController;
use Johncms\Modules\Profile\Application\Controllers\LinkedAccountsController;
use Johncms\Modules\Profile\Application\Controllers\SessionsController;
use Johncms\Modules\Profile\Application\Controllers\SettingsController;
use Johncms\Modules\Profile\Application\Controllers\StatisticsController;
use Johncms\Modules\Profile\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Routes migrated to the new architecture (require an authenticated user)
    $profileGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/profile/account', AccountController::class)->name('profile.account');
        $r->get('/profile/accounts', [LinkedAccountsController::class, 'index'])->name('profile.accounts');
        $r->post('/profile/accounts', [LinkedAccountsController::class, 'detach'])->name('profile.accounts.detach');
        $r->get('/profile/sessions', [SessionsController::class, 'index'])->name('profile.sessions');
        $r->post('/profile/sessions', [SessionsController::class, 'revoke'])->name('profile.sessions.revoke');
        $r->post('/profile/sessions/close-others', [SessionsController::class, 'revokeOthers'])
            ->name('profile.sessions.revoke-others');
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
        $r->get('/profile/{id:number}/edit/avatar', [AvatarController::class, 'form'])->name('profile.edit.avatar');
        $r->post('/profile/{id:number}/edit/avatar', [AvatarController::class, 'upload'])->name('profile.edit.avatar.upload');
        $r->get('/profile/{id:number}/edit/photo', [PhotoController::class, 'form'])->name('profile.edit.photo');
        $r->post('/profile/{id:number}/edit/photo', [PhotoController::class, 'upload'])->name('profile.edit.photo.upload');
        $r->get('/profile/{id:number}/activity', [ActivityController::class, 'messages'])->name('profile.activity');
        $r->get('/profile/{id:number}/activity/topics', [ActivityController::class, 'topics'])->name('profile.activity.topics');
        $r->get('/profile/{id:number}/activity/comments', [ActivityController::class, 'comments'])->name('profile.activity.comments');
        $r->get('/profile/{id:number}/karma', [KarmaController::class, 'index'])->name('profile.karma');
        $r->get('/profile/{id:number}/karma/new', [KarmaController::class, 'newResponses'])->name('profile.karma.new');
        $r->get('/profile/{id:number}/karma/vote', [KarmaController::class, 'voteForm'])->name('profile.karma.vote');
        $r->post('/profile/{id:number}/karma/vote', [KarmaController::class, 'vote'])->name('profile.karma.vote.submit');
        $r->get('/profile/{id:number}/karma/clean', [KarmaController::class, 'cleanForm'])->name('profile.karma.clean');
        $r->post('/profile/{id:number}/karma/clean', [KarmaController::class, 'clean'])->name('profile.karma.clean.confirm');
        $r->get('/profile/{id:number}/karma/delete/{voteId:number}', [KarmaController::class, 'deleteForm'])->name('profile.karma.delete');
        $r->post('/profile/{id:number}/karma/delete/{voteId:number}', [KarmaController::class, 'delete'])->name('profile.karma.delete.confirm');
        $r->get('/profile/{id:number}/bans', [BanController::class, 'history'])->name('profile.bans');
        $r->get('/profile/{id:number}/bans/new', [BanController::class, 'createForm'])->name('profile.bans.new');
        $r->post('/profile/{id:number}/bans/new', [BanController::class, 'create'])->name('profile.bans.new.submit');
        $r->get('/profile/{id:number}/bans/clear', [BanController::class, 'clearForm'])->name('profile.bans.clear');
        $r->post('/profile/{id:number}/bans/clear', [BanController::class, 'clear'])->name('profile.bans.clear.confirm');
        $r->get('/profile/{id:number}/bans/{banId:number}/cancel', [BanController::class, 'cancelForm'])->name('profile.bans.cancel');
        $r->post('/profile/{id:number}/bans/{banId:number}/cancel', [BanController::class, 'cancel'])->name('profile.bans.cancel.confirm');
        $r->get('/profile/{id:number}/bans/{banId:number}/delete', [BanController::class, 'deleteForm'])->name('profile.bans.delete');
        $r->post('/profile/{id:number}/bans/{banId:number}/delete', [BanController::class, 'delete'])->name('profile.bans.delete.confirm');
        $r->get('/profile/{id:number}', ProfileController::class)->name('profile.view');
    });
    $profileGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
