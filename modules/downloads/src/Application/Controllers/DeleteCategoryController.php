<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\UseCases\DeleteCategoryUseCase;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteCategoryController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private DeleteCategoryUseCase $deleteCategoryUseCase,
        private DownloadCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): Response
    {
        $subcategoryCount = DownloadCategory::query()->where('refid', $id)->count();
        $category = DownloadCategory::query()->find($id);

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Delete Folder'));

        $this->render->addData([
            'title'      => __('Delete Folder'),
            'page_title' => __('Delete Folder'),
        ]);

        if ($subcategoryCount > 0) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Delete Folder'),
                'type'          => 'alert-danger',
                'message'       => __('Before removing, delete subdirectories'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ]));
        }

        if ($category === null) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Delete Folder'),
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ]), Response::HTTP_NOT_FOUND);
        }

        if ($this->request->getMethod() === 'POST') {
            $refid = (int) $category->refid;
            $this->deleteCategoryUseCase->execute($category);
            $redirectUrl = $refid > 0
                ? ($this->categoryPathService->getCategoryUrlById($refid) ?? '/downloads/')
                : '/downloads/';

            return new RedirectResponse($redirectUrl);
        }

        return new Response($this->render->render('downloads::folder_delete', [
            'folder_name' => htmlspecialchars($category->rus_name),
            'action_url'  => '/downloads/categories/' . $id . '/delete',
            'back_url'    => $this->categoryPathService->getCategoryUrl($category),
        ]));
    }
}
