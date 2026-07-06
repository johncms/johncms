<?php

declare(strict_types=1);

use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionsAdminController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    // Admin panel. Public output (URL resolver) routes are added in a later iteration.
    if ($user->rights >= 9 && $user->isValid()) {
        $router->get('/admin/collections', [CollectionsAdminController::class, 'index'])->name('collections.admin.index');
        $router->get('/admin/collections/new', [CollectionsAdminController::class, 'newForm'])->name('collections.admin.new');
        $router->post('/admin/collections', [CollectionsAdminController::class, 'store'])->name('collections.admin.store');
        $router->get('/admin/collections/{id:number}/edit', [CollectionsAdminController::class, 'editForm'])->name('collections.admin.edit');
        $router->get('/admin/collections/{id:number}/delete', [CollectionsAdminController::class, 'deleteConfirm'])->name('collections.admin.delete_confirm');
        $router->post('/admin/collections/{id:number}/delete', [CollectionsAdminController::class, 'delete'])->name('collections.admin.delete');
    }
};
