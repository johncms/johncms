<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Exception;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Domain\Services\ScreenService;
use Intervention\Image\ImageManager;
use Johncms\FileInfo;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;

final readonly class EditScreenController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private NavChain $navChain,
        private ImageManager $imageManager,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): string
    {
        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null) {
            return $this->notFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $doParam = $this->request->getQuery('do');
            if ($doParam !== null) {
                return $this->handleDelete($id, $doParam);
            }

            return $this->handleUpload($id);
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
        $this->navChain->add(__('Managing Screenshots'));

        return $this->render->render('downloads::edit_screen', [
            'id'           => $id,
            'screens'      => ScreenService::getScreens($id),
            'delete_token' => $deleteToken,
            'action_url'   => '/downloads/edit-screen/' . $id . '/',
            'file_url'     => $this->filePathService->getFileUrl($file),
        ]);
    }

    private function handleDelete(int $id, string $filename): string
    {
        $post = $this->request->getParsedBody();
        $sessionToken = $this->session->get('delete_token');

        if (
            isset($post['delete_token']) &&
            $sessionToken !== null &&
            $sessionToken === $post['delete_token']
        ) {
            $screensDir = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $id . \DS;
            $fileInfo = new FileInfo($screensDir . trim($filename));
            if ($fileInfo->isFile()) {
                unlink($screensDir . $fileInfo->getFilename());
            }
        }

        http_response_code(302);
        header('Location: /downloads/edit-screen/' . $id . '/');
        exit;
    }

    private function handleUpload(int $id): string
    {
        $uploadUrl = '/downloads/edit-screen/' . $id . '/';
        $screensDir = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $id;

        if (! is_dir($screensDir) && ! mkdir($screensDir, 0777) && ! is_dir($screensDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $screensDir));
        }

        $files = $this->request->getUploadedFiles();
        if (empty($files) || empty($files['screen'])) {
            return $this->render->render('system::pages/result', [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-danger',
                'message'       => __('Screenshot not attached'),
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        /** @var \GuzzleHttp\Psr7\UploadedFile $screenshot */
        $screenshot = $files['screen'];
        $fileName = $screensDir . \DS . $id . '.png';
        if (file_exists($fileName)) {
            $fileName = $screensDir . \DS . time() . '_' . $id . '.png';
        }

        try {
            $img = $this->imageManager->make($screenshot->getStream());
            $img->resize(1920, 1080, static function ($constraint): void {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($fileName, 100, 'png');

            return $this->render->render('system::pages/result', [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-success',
                'message'       => __('Screenshot is attached'),
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Back'),
            ]);
        } catch (Exception $e) {
            return $this->render->render('system::pages/result', [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-danger',
                'message'       => __('Screenshot not attached') . ' ' . $e->getMessage(),
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }
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
