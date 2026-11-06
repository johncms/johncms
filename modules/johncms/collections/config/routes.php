<?php

declare(strict_types=1);

use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionBulkAdminController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionFieldsAdminController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionFilesController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionItemPreviewController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionItemsAdminController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionRelationsController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionSectionsAdminController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionSlugController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionTagsController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionUploadController;
use Johncms\Modules\Collections\Application\Controllers\Admin\CollectionsAdminController;
use Johncms\Modules\Collections\Application\Controllers\CollectionRouterController;
use Johncms\Modules\Collections\Application\Middleware\CollectionContentAccessMiddleware;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Admin panel. Public output (URL resolver) routes are added in a later iteration.
    $admin = $router->group('', function (RouteCollection $router): void {
        $router->get('/admin/collections', [CollectionsAdminController::class, 'index'])
            ->name('collections.admin.index')
            ->permission(CollectionsPermissions::MANAGE);
        $router->get('/admin/collections/new', [CollectionsAdminController::class, 'newForm'])
            ->name('collections.admin.new')
            ->permission(CollectionsPermissions::MANAGE);
        $router->post('/admin/collections', [CollectionsAdminController::class, 'store'])
            ->name('collections.admin.store')
            ->permission(CollectionsPermissions::MANAGE);
        $router->get('/admin/collections/{id:number}/settings', [CollectionsAdminController::class, 'settings'])
            ->name('collections.admin.settings')
            ->permission(CollectionsPermissions::MANAGE);
        $router->get('/admin/collections/{id:number}/delete', [CollectionsAdminController::class, 'deleteConfirm'])
            ->name('collections.admin.delete_confirm')
            ->permission(CollectionsPermissions::MANAGE);
        $router->post('/admin/collections/{id:number}/delete', [CollectionsAdminController::class, 'delete'])
            ->name('collections.admin.delete')
            ->permission(CollectionsPermissions::MANAGE);

        $router->post('/admin/collections/slug', CollectionSlugController::class)
            ->name('collections.admin.slug')
            ->permission(CollectionsPermissions::MANAGE);
        $router->post('/admin/collections/{collection_id:number}/slug', CollectionSlugController::class)
            ->name('collections.admin.content_slug')
            ->middleware(CollectionContentAccessMiddleware::class);

        // Fields of a collection.
        $router->get('/admin/collections/{collection_id:number}/fields', [CollectionFieldsAdminController::class, 'index'])
            ->name('collections.admin.fields')
            ->permission(CollectionsPermissions::MANAGE);
        $router->get('/admin/collections/{collection_id:number}/fields/new', [CollectionFieldsAdminController::class, 'newForm'])
            ->name('collections.admin.fields.new')
            ->permission(CollectionsPermissions::MANAGE);
        $router->post('/admin/collections/{collection_id:number}/fields', [CollectionFieldsAdminController::class, 'store'])
            ->name('collections.admin.fields.store')
            ->permission(CollectionsPermissions::MANAGE);
        $router->get('/admin/collections/{collection_id:number}/fields/{id:number}/edit', [CollectionFieldsAdminController::class, 'editForm'])
            ->name('collections.admin.fields.edit')
            ->permission(CollectionsPermissions::MANAGE);
        $router->get('/admin/collections/{collection_id:number}/fields/{id:number}/delete', [CollectionFieldsAdminController::class, 'deleteConfirm'])
            ->name('collections.admin.fields.delete_confirm')
            ->permission(CollectionsPermissions::MANAGE);
        $router->post('/admin/collections/{collection_id:number}/fields/{id:number}/delete', [CollectionFieldsAdminController::class, 'delete'])
            ->name('collections.admin.fields.delete')
            ->permission(CollectionsPermissions::MANAGE);

        // Sections of a collection (hierarchical).
        $router->get('/admin/collections/{collection_id:number}/sections', [CollectionSectionsAdminController::class, 'index'])
            ->name('collections.admin.sections')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->get('/admin/collections/{collection_id:number}/sections/new', [CollectionSectionsAdminController::class, 'newForm'])
            ->name('collections.admin.sections.new')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->post('/admin/collections/{collection_id:number}/sections', [CollectionSectionsAdminController::class, 'store'])
            ->name('collections.admin.sections.store')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->get('/admin/collections/{collection_id:number}/sections/{id:number}/edit', [CollectionSectionsAdminController::class, 'editForm'])
            ->name('collections.admin.sections.edit')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->get('/admin/collections/{collection_id:number}/sections/{id:number}/delete', [CollectionSectionsAdminController::class, 'deleteConfirm'])
            ->name('collections.admin.sections.delete_confirm')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->post('/admin/collections/{collection_id:number}/sections/{id:number}/delete', [CollectionSectionsAdminController::class, 'delete'])
            ->name('collections.admin.sections.delete')
            ->middleware(CollectionContentAccessMiddleware::class);

        $router->get('/admin/collections/{collection_id:number}/items/{id:number}/preview', CollectionItemPreviewController::class)
            ->name('collections.admin.items.preview')
            ->middleware(CollectionContentAccessMiddleware::class);

        $router->post('/admin/collections/{collection_id:number}/uploads/context', [CollectionUploadController::class, 'createContext'])
            ->name('collections.admin.uploads.context')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->post('/admin/collections/{collection_id:number}/uploads', [CollectionUploadController::class, 'upload'])
            ->name('collections.admin.uploads.store')
            ->middleware(CollectionContentAccessMiddleware::class);

        $router->get('/admin/collections/{collection_id:number}/tags/{code}', CollectionTagsController::class)
            ->requirements(['code' => '[a-z0-9_-]+'])
            ->name('collections.admin.tags')
            ->middleware(CollectionContentAccessMiddleware::class);

        $router->get('/admin/collections/{collection_id:number}/relations/{code}', CollectionRelationsController::class)
            ->requirements(['code' => '[a-z0-9_-]+'])
            ->name('collections.admin.relations')
            ->middleware(CollectionContentAccessMiddleware::class);

        $router->get('/admin/collections/{collection_id:number}/files/{code}', CollectionFilesController::class)
            ->requirements(['code' => '[a-z0-9_-]+'])
            ->name('collections.admin.files')
            ->middleware(CollectionContentAccessMiddleware::class);

        $router->post('/admin/collections/{collection_id:number}/items/bulk', CollectionBulkAdminController::class)
            ->name('collections.admin.items.bulk')
            ->middleware(CollectionContentAccessMiddleware::class);

        // Items of a collection (with custom EAV fields).
        $router->get('/admin/collections/{collection_id:number}/items', [CollectionItemsAdminController::class, 'index'])
            ->name('collections.admin.items')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->get('/admin/collections/{collection_id:number}/items/new', [CollectionItemsAdminController::class, 'newForm'])
            ->name('collections.admin.items.new')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->post('/admin/collections/{collection_id:number}/items', [CollectionItemsAdminController::class, 'store'])
            ->name('collections.admin.items.store')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->get('/admin/collections/{collection_id:number}/items/{id:number}/edit', [CollectionItemsAdminController::class, 'editForm'])
            ->name('collections.admin.items.edit')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->get('/admin/collections/{collection_id:number}/items/{id:number}/delete', [CollectionItemsAdminController::class, 'deleteConfirm'])
            ->name('collections.admin.items.delete_confirm')
            ->middleware(CollectionContentAccessMiddleware::class);
        $router->post('/admin/collections/{collection_id:number}/items/{id:number}/delete', [CollectionItemsAdminController::class, 'delete'])
            ->name('collections.admin.items.delete')
            ->middleware(CollectionContentAccessMiddleware::class);
    });
    $admin->adminArea();

    // Public output. Low-priority catch-all that maps root URLs to collections;
    // real module routes have higher priority and win first. The custom
    // requirement allows nested paths and the ".html" detail suffix.
    $router->get('/{route}', CollectionRouterController::class)
        ->requirements(['route' => '[\w/.+-]+'])
        ->priority(-1000)
        ->name('collections.public');
};
