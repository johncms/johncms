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
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;

final readonly class DeleteFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private NavChain $navChain,
        private DeleteFileUseCase $deleteFileUseCase,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
        private DownloadCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): string
    {
        if ($this->request->getMethod() === 'POST') {
            return $this->handleDelete($id);
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

        return $this->render->render('downloads::delete_file', [
            'id'           => $id,
            'delete_token' => $deleteToken,
            'action_url'   => '/downloads/delete-file/' . $id . '/',
            'back_url'     => $this->filePathService->getFileUrl($file),
        ]);
    }

    private function handleDelete(int $id): string
    {
        $post = $this->request->getParsedBody();
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
        http_response_code(302);
        header('Location: ' . $redirectUrl);
        exit;
    }

    private function notFound(): string
    {
        http_response_code(404);
        return $this->render->render('system::pages/result', [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ]);
    }
}
