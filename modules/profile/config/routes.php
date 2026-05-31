<?php

declare(strict_types=1);

use Johncms\Modules\Profile\Application\Controllers\ActivityController;
use Johncms\Modules\Profile\Application\Controllers\IpHistoryController;
use Johncms\Modules\Profile\Application\Controllers\ProfileController;
use Johncms\Modules\Profile\Application\Controllers\StatisticsController;
use Johncms\Modules\Profile\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    // Routes migrated to the new architecture (require an authenticated user)
    $profileGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/profile/{id:number}/statistics', StatisticsController::class)->name('profile.statistics');
        $r->get('/profile/{id:number}/ip-history', IpHistoryController::class)->name('profile.ip-history');
        $r->get('/profile/{id:number}/activity', [ActivityController::class, 'messages'])->name('profile.activity');
        $r->get('/profile/{id:number}/activity/topics', [ActivityController::class, 'topics'])->name('profile.activity.topics');
        $r->get('/profile/{id:number}/activity/comments', [ActivityController::class, 'comments'])->name('profile.activity.comments');
        $r->get('/profile/{id:number}', ProfileController::class)->name('profile.view');
    });
    $profileGroup->addMiddleware(AuthorizedUserMiddleware::class);

    // Legacy routes (gradually being migrated)
    $router->map(['GET', 'POST'], '/profile/skl.php', 'modules/profile/skl.php')->name('profile.skl');
    $router->map(['GET', 'POST'], '/profile', 'modules/profile/index.php')->name('profile.index');
};
