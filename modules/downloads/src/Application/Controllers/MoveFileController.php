<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\UseCases\MoveFileUseCase;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class MoveFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private MoveFileUseCase $moveFileUseCase,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): Response
    {
        if ($this->currentUser->rights <= 6) {
            return $this->notFound();
        }

        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            return $this->notFound();
        }

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->categoryNavService->buildForFileDir($file->dir);
        $this->navChain->add(htmlspecialchars($file->rus_name), $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Move File'));

        $baseUrl = '/downloads/move-file/' . $id . '/';
        $catId = (int) ($this->request->queryParam('catId') ?? 0);
        $do = $this->request->queryParam('do') ?? '';

        $category = $catId ? DownloadCategory::query()->find($catId) : null;
        if ($catId && $category === null) {
            $catId = 0;
        }

        if ($do === 'transfer' && $catId) {
            return $this->handleTransfer($id, $file, $catId, $category, $baseUrl);
        }

        return $this->showBrowser($id, $file, $catId, $baseUrl);
    }

    private function showBrowser(int $id, DownloadFile $file, int $catId, string $baseUrl): Response
    {
        $sections = DownloadCategory::query()
            ->where('refid', $catId)
            ->get()
            ->map(function (DownloadCategory $cat) use ($id, $file, $baseUrl): array {
                return [
                    'rus_name'         => htmlspecialchars($cat->rus_name),
                    'section_open_url' => $baseUrl . '?catId=' . $cat->id,
                    'section_move_url' => $cat->id !== (int) $file->refid
                        ? $baseUrl . '?catId=' . $cat->id . '&do=transfer'
                        : '',
                ];
            })->all();

        $moveToCurrentUrl = '';
        if ($catId && $catId !== (int) $file->refid) {
            $moveToCurrentUrl = $baseUrl . '?catId=' . $catId . '&do=transfer';
        }

        $this->render->addData([
            'title'      => __('Move File'),
            'page_title' => __('Move File'),
        ]);

        return new Response($this->render->render('downloads::move_file', [
            'id'       => $id,
            'sections' => $sections,
            'back_url' => $this->filePathService->getFileUrl($file),
            'urls'     => ['move_to_current_url' => $moveToCurrentUrl],
        ]));
    }

    private function handleTransfer(int $id, DownloadFile $file, int $catId, DownloadCategory $category, string $baseUrl): Response
    {
        if ($catId === (int) $file->refid) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Move File'),
                'type'          => 'alert-info',
                'message'       => __('This is the current directory'),
                'back_url'      => $baseUrl . '?catId=' . $catId,
                'back_url_name' => __('Back'),
            ]));
        }

        if ($this->request->query->has('yes')) {
            $this->moveFileUseCase->execute($file, $category);

            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Move File'),
                'type'          => 'alert-success',
                'message'       => __('The file has been moved'),
                'back_url'      => '/downloads/recount',
                'back_url_name' => __('Update counters'),
            ]));
        }

        $pageTitle = htmlspecialchars($file->rus_name);
        $this->render->addData([
            'title'      => $pageTitle,
            'page_title' => $pageTitle,
        ]);

        return new Response($this->render->render('downloads::move_file_confirm', [
            'id'         => $id,
            'action_url' => $baseUrl . '?catId=' . $catId . '&do=transfer&yes',
            'back_url'   => $this->filePathService->getFileUrl($file),
        ]));
    }

    private function notFound(): Response
    {
        return new Response($this->render->render('system::pages/result', [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ]), Response::HTTP_NOT_FOUND);
    }
}
