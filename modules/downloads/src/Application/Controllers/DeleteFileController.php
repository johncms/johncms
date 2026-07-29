<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Application\UseCases\DeleteFileUseCase;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Session $session,
        private NavChain $navChain,
        private DeleteFileUseCase $deleteFileUseCase,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
        private DownloadCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(Request $request, int $id): Response
    {
        if ($request->getMethod() === 'POST') {
            return $this->handleDelete($request, $id);
        }

        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            return $this->notFound();
        }

        $deleteToken = uniqid('', true);
        $this->session->set('delete_token', $deleteToken);

        $pageTitle = htmlspecialchars($file->rus_name);
        $this->render->addData([
            'title'      => $pageTitle,
            'page_title' => $pageTitle,
        ]);

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->categoryNavService->buildForFileDir($file->dir);
        $this->navChain->add($pageTitle, $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Delete File'));

        return new Response($this->render->render('downloads::delete_file', [
            'id'           => $id,
            'delete_token' => $deleteToken,
            'action_url'   => '/downloads/delete-file/' . $id . '/',
            'back_url'     => $this->filePathService->getFileUrl($file),
        ]));
    }

    private function handleDelete(Request $request, int $id): Response
    {
        $post = $request->request->all();
        $sessionToken = $this->session->get('delete_token');

        if (
            ! isset($post['delete_token']) ||
            $sessionToken === null ||
            $sessionToken !== $post['delete_token']
        ) {
            return $this->notFound();
        }

        $refid = (int) (DownloadFile::query()->select('refid')->find($id)?->refid ?? 0);

        try {
            $this->deleteFileUseCase->execute($id);
        } catch (FileNotFoundException) {
            return $this->notFound();
        }

        $redirectUrl = $refid > 0
            ? ($this->categoryPathService->getCategoryUrlById($refid) ?? '/downloads/')
            : '/downloads/';

        return new RedirectResponse($redirectUrl);
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
