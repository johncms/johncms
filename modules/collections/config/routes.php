<?php

declare(strict_types=1);

use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionFieldsAdminController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionItemsAdminController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionSectionsAdminController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionsAdminController;
use Johncms\Modules\Collections\Application\Controllers\CollectionRouterController;
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

        // Fields of a collection.
        $router->get('/admin/collections/{collection_id:number}/fields', [CollectionFieldsAdminController::class, 'index'])->name('collections.admin.fields');
        $router->get('/admin/collections/{collection_id:number}/fields/new', [CollectionFieldsAdminController::class, 'newForm'])->name('collections.admin.fields.new');
        $router->post('/admin/collections/{collection_id:number}/fields', [CollectionFieldsAdminController::class, 'store'])->name('collections.admin.fields.store');
        $router->get('/admin/collections/{collection_id:number}/fields/{id:number}/edit', [CollectionFieldsAdminController::class, 'editForm'])->name('collections.admin.fields.edit');
        $router->get('/admin/collections/{collection_id:number}/fields/{id:number}/delete', [CollectionFieldsAdminController::class, 'deleteConfirm'])->name('collections.admin.fields.delete_confirm');
        $router->post('/admin/collections/{collection_id:number}/fields/{id:number}/delete', [CollectionFieldsAdminController::class, 'delete'])->name('collections.admin.fields.delete');

        // Sections of a collection (hierarchical).
        $router->get('/admin/collections/{collection_id:number}/sections', [CollectionSectionsAdminController::class, 'index'])->name('collections.admin.sections');
        $router->get('/admin/collections/{collection_id:number}/sections/new', [CollectionSectionsAdminController::class, 'newForm'])->name('collections.admin.sections.new');
        $router->post('/admin/collections/{collection_id:number}/sections', [CollectionSectionsAdminController::class, 'store'])->name('collections.admin.sections.store');
        $router->get('/admin/collections/{collection_id:number}/sections/{id:number}/edit', [CollectionSectionsAdminController::class, 'editForm'])->name('collections.admin.sections.edit');
        $router->get('/admin/collections/{collection_id:number}/sections/{id:number}/delete', [CollectionSectionsAdminController::class, 'deleteConfirm'])->name('collections.admin.sections.delete_confirm');
        $router->post('/admin/collections/{collection_id:number}/sections/{id:number}/delete', [CollectionSectionsAdminController::class, 'delete'])->name('collections.admin.sections.delete');

        // Items of a collection (with custom EAV fields).
        $router->get('/admin/collections/{collection_id:number}/items', [CollectionItemsAdminController::class, 'index'])->name('collections.admin.items');
        $router->get('/admin/collections/{collection_id:number}/items/new', [CollectionItemsAdminController::class, 'newForm'])->name('collections.admin.items.new');
        $router->post('/admin/collections/{collection_id:number}/items', [CollectionItemsAdminController::class, 'store'])->name('collections.admin.items.store');
        $router->get('/admin/collections/{collection_id:number}/items/{id:number}/edit', [CollectionItemsAdminController::class, 'editForm'])->name('collections.admin.items.edit');
        $router->get('/admin/collections/{collection_id:number}/items/{id:number}/delete', [CollectionItemsAdminController::class, 'deleteConfirm'])->name('collections.admin.items.delete_confirm');
        $router->post('/admin/collections/{collection_id:number}/items/{id:number}/delete', [CollectionItemsAdminController::class, 'delete'])->name('collections.admin.items.delete');
    }

    // Public output. Low-priority catch-all that maps root URLs to collections;
    // real module routes have higher priority and win first. The custom
    // requirement allows nested paths and the ".html" detail suffix.
    $router->get('/{route}', CollectionRouterController::class)
        ->requirements(['route' => '[\w/.+-]+'])
        ->priority(-1000)
        ->name('collections.public');
};
