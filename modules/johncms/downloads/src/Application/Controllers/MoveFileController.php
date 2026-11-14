<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\UseCases\MoveFileUseCase;
use Johncms\Modules\Downloads\Application\UseCases\RecountCategoriesUseCase;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class MoveFileController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private MoveFileUseCase $moveFileUseCase,
        private RecountCategoriesUseCase $recount,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        if (! $this->accessChecker->allows(DownloadsPermissions::FILE_MOVE)) {
            return $this->notFound();
        }

        /** @var DownloadFile|null $file */
        $file = DownloadFile::query()
            ->whereKey($id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            return $this->notFound();
        }

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->categoryNavService->buildForFileDir($file->dir);
        $this->navChain->add($file->rus_name, $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Move File'));

        $baseUrl = '/downloads/move-file/' . $id . '/';

        if ($request->isPost()) {
            return $this->move($request->bodyInt('catId'), $file, $baseUrl);
        }

        $catId = $request->queryInt('catId');

        if ($catId && ! DownloadCategory::query()->whereKey($catId)->exists()) {
            $catId = 0;
        }

        return $this->showBrowser($file, $catId, $baseUrl);
    }

    private function showBrowser(DownloadFile $file, int $catId, string $baseUrl): ViewResponse
    {
        $currentId = (int) $file->refid;
        $sections = DownloadCategory::query()
            ->where('refid', $catId)
            ->get()
            ->map(static fn (DownloadCategory $cat): array => [
                'id'               => $cat->id,
                'rus_name'         => $cat->rus_name,
                'section_open_url' => $baseUrl . '?catId=' . $cat->id,
                'can_move'         => $cat->id !== $currentId,
            ])->all();

        return new ViewResponse('@downloads/public/move-file.twig', [
            'title'      => __('Move File'),
            'page_title' => __('Move File'),
            'file_name'  => $file->rus_name,
            'sections'   => $sections,
            'action_url' => $baseUrl,
            'current_id' => $catId !== $currentId ? $catId : 0,
            'back_url'   => $this->filePathService->getFileUrl($file),
        ]);
    }

    /** Carried out only from the confirmed form of the browser (RequireConfirmationMiddleware). */
    private function move(int $catId, DownloadFile $file, string $baseUrl): ViewResponse
    {
        $category = $catId ? DownloadCategory::query()->find($catId) : null;

        if ($category === null) {
            return $this->notFound();
        }

        if ($catId === (int) $file->refid) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Move File'),
                'type'          => 'alert-info',
                'message'       => __('This is the current directory'),
                'back_url'      => $baseUrl . '?catId=' . $catId,
                'back_url_name' => __('Back'),
            ]);
        }

        $this->moveFileUseCase->execute($file, $category);
        $this->recount->execute();

        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Move File'),
            'type'          => 'alert-success',
            'message'       => __('The file has been moved'),
            'back_url'      => $this->filePathService->getFileUrl($file->refresh()),
            'back_url_name' => __('Back'),
        ]);
    }

    private function notFound(): ViewResponse
    {
        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ], Response::HTTP_NOT_FOUND);
    }
}
