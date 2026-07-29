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
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditScreenController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Session $session,
        private NavChain $navChain,
        private ImageManager $imageManager,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(Request $request, int $id): Response
    {
        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null) {
            return $this->notFound();
        }

        if ($request->getMethod() === 'POST') {
            $doParam = $request->queryParam('do');
            if ($doParam !== '') {
                return $this->handleDelete($request, $id, $doParam);
            }

            return $this->handleUpload($request, $id);
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

        return new Response($this->render->render('downloads::edit_screen', [
            'id'           => $id,
            'screens'      => ScreenService::getScreens($id),
            'delete_token' => $deleteToken,
            'action_url'   => '/downloads/edit-screen/' . $id . '/',
            'file_url'     => $this->filePathService->getFileUrl($file),
        ]));
    }

    private function handleDelete(Request $request, int $id, string $filename): Response
    {
        $post = $request->request->all();
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

        return new RedirectResponse('/downloads/edit-screen/' . $id . '/');
    }

    private function handleUpload(Request $request, int $id): Response
    {
        $uploadUrl = '/downloads/edit-screen/' . $id . '/';
        $screensDir = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $id;

        if (! is_dir($screensDir) && ! mkdir($screensDir, 0777) && ! is_dir($screensDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $screensDir));
        }

        $files = $request->files->all();
        if (empty($files) || empty($files['screen'])) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-danger',
                'message'       => __('Screenshot not attached'),
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Repeat'),
            ]));
        }

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $screenshot */
        $screenshot = $files['screen'];
        $fileName = $screensDir . \DS . $id . '.png';
        if (file_exists($fileName)) {
            $fileName = $screensDir . \DS . time() . '_' . $id . '.png';
        }

        try {
            $img = $this->imageManager->make($screenshot->getPathname());
            $img->resize(1920, 1080, static function ($constraint): void {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($fileName, 100, 'png');
            // Rendering stays outside the try: the catch below reports an image-processing
            // failure, and a template error must not be misreported as one (nor have its raw
            // message printed to the visitor).
        } catch (Exception $e) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-danger',
                'message'       => __('Screenshot not attached') . ' ' . $e->getMessage(),
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Repeat'),
            ]));
        }

        return new Response($this->render->render('system::pages/result', [
            'title'         => __('Upload screenshot'),
            'type'          => 'alert-success',
            'message'       => __('Screenshot is attached'),
            'back_url'      => $uploadUrl,
            'back_url_name' => __('Back'),
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
