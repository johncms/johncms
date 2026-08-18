<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Domain\Services\ScreenService;
use Johncms\FileInfo;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditScreenController
{
    private const int SCREENSHOT_WIDTH = 1920;
    private const int SCREENSHOT_HEIGHT = 1080;

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private ImageProcessorInterface $imageProcessor,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse|ViewResponse
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

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->categoryNavService->buildForFileDir($file->dir);
        $this->navChain->add($file->rus_name, $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Managing Screenshots'));

        return new ViewResponse('@downloads/public/edit-screenshots.twig', [
            'title'        => $file->rus_name,
            'page_title'   => $file->rus_name,
            'screens'      => ScreenService::getScreens($id),
            'delete_token' => $deleteToken,
            'action_url'   => '/downloads/edit-screen/' . $id . '/',
            'file_url'     => $this->filePathService->getFileUrl($file),
        ]);
    }

    private function handleDelete(Request $request, int $id, string $filename): RedirectResponse
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

    private function handleUpload(Request $request, int $id): ViewResponse
    {
        $uploadUrl = '/downloads/edit-screen/' . $id . '/';
        $screensDir = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $id;

        if (! is_dir($screensDir) && ! mkdir($screensDir, 0777) && ! is_dir($screensDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $screensDir));
        }

        $files = $request->files->all();
        if (empty($files) || empty($files['screen'])) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-danger',
                'message'       => __('Screenshot not attached'),
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $screenshot */
        $screenshot = $files['screen'];
        $fileName = $screensDir . \DS . $id . '.png';
        if (file_exists($fileName)) {
            $fileName = $screensDir . \DS . time() . '_' . $id . '.png';
        }

        try {
            $this->imageProcessor->saveScaledDown(
                $screenshot->getPathname(),
                $fileName,
                self::SCREENSHOT_WIDTH,
                self::SCREENSHOT_HEIGHT
            );
            // Rendering stays outside the try: the catch below reports an image-processing
            // failure, and a template error must not be misreported as one (nor have its raw
            // message printed to the visitor).
        } catch (ImageProcessingException $e) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-danger',
                'message'       => __('Screenshot not attached') . ' ' . $e->getMessage(),
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Upload screenshot'),
            'type'          => 'alert-success',
            'message'       => __('Screenshot is attached'),
            'back_url'      => $uploadUrl,
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
