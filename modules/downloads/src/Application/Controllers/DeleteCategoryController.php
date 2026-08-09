<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\UseCases\DeleteCategoryUseCase;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteCategoryController
{
    public function __construct(
        private NavChain $navChain,
        private DeleteCategoryUseCase $deleteCategoryUseCase,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse|ViewResponse
    {
        $subcategoryCount = DownloadCategory::query()->where('refid', $id)->count();
        $category = DownloadCategory::query()->find($id);

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Delete Folder'));

        if ($subcategoryCount > 0) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Delete Folder'),
                'type'          => 'alert-danger',
                'message'       => __('Before removing, delete subdirectories'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ]);
        }

        if ($category === null) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Delete Folder'),
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ], Response::HTTP_NOT_FOUND);
        }

        if ($request->getMethod() === 'POST') {
            $refid = (int) $category->refid;
            $this->deleteCategoryUseCase->execute($category);
            $redirectUrl = $refid > 0
                ? ($this->categoryPathService->getCategoryUrlById($refid) ?? '/downloads/')
                : '/downloads/';

            return new RedirectResponse($redirectUrl);
        }

        return new ViewResponse('@downloads/public/delete-category.twig', [
            'title'       => __('Delete Folder'),
            'page_title'  => __('Delete Folder'),
            'folder_name' => $category->rus_name,
            'action_url'  => '/downloads/categories/' . $id . '/delete',
            'back_url'    => $this->categoryPathService->getCategoryUrl($category),
        ]);
    }
}
