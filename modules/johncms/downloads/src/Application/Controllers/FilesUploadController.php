<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Downloads\Application\Access\DownloadsAccess;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\FileInfo;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Services\DownloadSlugService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final readonly class FilesUploadController
{
    private const int SCREENSHOT_WIDTH = 1920;
    private const int SCREENSHOT_HEIGHT = 1080;

    /** The outcome of an upload, carried over the redirect to the page that shows it. */
    private const string RESULT = 'downloads_upload_result';

    private const array DEFAULT_EXTENSIONS = [
        'mp4', 'rar', 'zip', 'pdf', 'nth', 'txt', 'tar', 'gz',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg',
        'thm', 'jad', 'jar', 'cab', 'sis', 'sisx', 'exe', 'msi',
        'apk', 'djvu', 'fb2', 'webm', 'avi', 'mov', 'aac', 'm4a',
    ];

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private ImageProcessorInterface $imageProcessor,
        private DownloadSlugService $slugService,
        private DownloadFilePathService $filePathService,
        private DownloadCategoryPathService $categoryPathService,
        private DownloadsAccess $downloadsAccess,
        private Session $session,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $category = DownloadCategory::query()->find($id);

        if ($category === null || ! is_dir($category->dir)) {
            return $this->error(__('The directory does not exist'), '/downloads/', Response::HTTP_NOT_FOUND);
        }

        $isAdmin = $this->accessChecker->allows(DownloadsPermissions::MODERATE);
        $canUpload = $isAdmin || $this->downloadsAccess->mayUpload($category);

        if (! $canUpload) {
            return $this->error(__('Access forbidden'), $this->categoryPathService->getCategoryUrl($category), Response::HTTP_FORBIDDEN);
        }

        $allowedExtensions = $category->field
            ? explode(', ', $category->text)
            : self::DEFAULT_EXTENSIONS;

        $this->navChain->add(__('Downloads'), '/downloads/');
        $categoryUrl = $this->categoryPathService->getCategoryUrl($category);
        $this->navChain->add($category->rus_name, $categoryUrl);
        $this->navChain->add(__('Upload File'));

        $form = [
            'category_url' => $categoryUrl,
            'category'     => $category->rus_name,
            'extensions'   => $allowedExtensions,
            'max_size'     => (int) config('johncms.flsz'),
            'moderated'    => ! $isAdmin,
        ];

        if ($request->isPost()) {
            return $this->handleUpload($request, $id, $category->dir, $allowedExtensions, $isAdmin, $form);
        }

        // The result of the upload just made, shown once after the redirect: a reload of the page
        // shows the form again instead of sending the file a second time.
        $result = $this->session->getFlash(self::RESULT);
        if (is_array($result)) {
            return new ViewResponse('@downloads/public/upload-result.twig', [
                'title'      => __('Upload File'),
                'page_title' => __('Upload File'),
                'id'         => $id,
                'category'   => ['name' => $category->rus_name, 'url' => $categoryUrl],
                ...$result,
            ]);
        }

        return $this->form($id, $form, ['name_link' => __('Download')], []);
    }

    /**
     * @param array<string, mixed>        $form
     * @param array<string, string>       $values
     * @param array<string, list<string>> $errors
     */
    private function form(int $id, array $form, array $values, array $errors): ViewResponse
    {
        return new ViewResponse('@downloads/public/upload.twig', [
            'title'      => __('Upload File'),
            'page_title' => __('Upload File'),
            'action_url' => '/downloads/upload/' . $id . '/',
            'form'       => $form,
            'values'     => $values,
            'errors'     => $errors,
        ], $errors ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    /**
     * @param list<string>         $allowedExtensions
     * @param array<string, mixed> $form
     */
    private function handleUpload(Request $request, int $id, string $categoryDir, array $allowedExtensions, bool $isAdmin, array $form): ViewResponse
    {
        $values = [
            'new_file'  => trim($request->body('new_file')),
            'text'      => trim($request->body('text')),
            'name_link' => trim($request->body('name_link')),
            'opis'      => trim($request->body('opis')),
        ];

        $result = $this->validator->validate($values, [
            'new_file'  => [new StringLength(max: 100, allowEmpty: true)],
            'text'      => [new StringLength(max: 200, allowEmpty: true)],
            'name_link' => [new StringLength(max: 200)],
        ]);

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('fail');
        $ext = '';

        if (! $uploadedFile instanceof UploadedFile || ! $uploadedFile->isValid()) {
            $result = $result->withError('fail', __('Choose the file to upload.'));
        } else {
            $ext = strtolower((new FileInfo($uploadedFile->getClientOriginalName()))->getExtension());
            $maxSize = (int) $form['max_size'];

            if ($uploadedFile->getSize() > 1024 * $maxSize) {
                $result = $result->withError('fail', __('The file is larger than %d KB.', $maxSize));
            }

            if (! in_array($ext, $allowedExtensions, true)) {
                $result = $result->withError('fail', __('Files of this type cannot be uploaded here.'));
            }
        }

        if (! $result->isValid()) {
            return $this->form($id, $form, $values, $result->getErrors());
        }

        $fileInfo = $values['new_file'] !== ''
            ? new FileInfo($values['new_file'] . '.' . $ext)
            : new FileInfo($uploadedFile->getClientOriginalName());
        $fname = $fileInfo->getCleanName();
        $displayName = $values['text'] !== '' ? $values['text'] : $fname;

        if (file_exists($categoryDir . '/' . $fname)) {
            $fname = time() . $fname;
        }

        try {
            $uploadedFile->move($categoryDir, $fname);
        } catch (FileException) {
            return $this->form($id, $form, $values, ['fail' => [__('The file could not be saved. Try again.')]]);
        }

        $moderation = ! $isAdmin;
        $slug = $this->slugService->generateUniqueFileSlug($displayName, $id);

        $file = DownloadFile::query()->create([
            'refid'    => $id,
            'dir'      => $categoryDir,
            'time'     => time(),
            'name'     => $fname,
            'slug'     => $slug,
            'text'     => $values['name_link'],
            'rus_name' => $displayName,
            'type'     => $moderation ? 3 : 2,
            'user_id'  => $this->currentUser->id(),
            'about'    => $values['opis'],
            'desc'     => '',
        ]);

        [$screenAttached, $screenError] = $this->attachScreenshot($request->files->get('screen'), $file->id);

        if (! $moderation) {
            $this->incrementCategoryCounters($id);
        }

        $this->session->flash(self::RESULT, [
            'file_name'       => $displayName,
            'file_url'        => $moderation ? '' : ($this->filePathService->getFileUrlById($file->id) ?? ''),
            'moderation'      => $moderation,
            'screen_attached' => $screenAttached,
            'screen_error'    => $screenError,
        ]);
        redirect('/downloads/upload/' . $id . '/');
    }

    /**
     * A screenshot is optional, and a broken one does not undo the file: the result says so.
     *
     * @return array{0: bool|null, 1: string} Null when no screenshot was sent at all.
     */
    private function attachScreenshot(mixed $screenshot, int $fileId): array
    {
        if (! $screenshot instanceof UploadedFile) {
            return [null, ''];
        }

        $screensDir = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $fileId;
        if (! is_dir($screensDir) && ! mkdir($screensDir, 0777, true) && ! is_dir($screensDir)) {
            return [false, ''];
        }

        try {
            $this->imageProcessor->saveScaledDown(
                $screenshot->getPathname(),
                $screensDir . '/' . $fileId . '.png',
                self::SCREENSHOT_WIDTH,
                self::SCREENSHOT_HEIGHT
            );
        } catch (ImageProcessingException $exception) {
            return [false, $exception->getMessage()];
        }

        return [true, ''];
    }

    private function incrementCategoryCounters(int $categoryId): void
    {
        $ids = [];
        $dirid = $categoryId;
        while ($dirid !== 0 && $dirid !== '') {
            $ids[] = $dirid;
            $cat = DownloadCategory::query()->select('refid')->find($dirid);
            if ($cat === null) {
                break;
            }
            $dirid = (int) $cat->refid;
        }

        if ($ids) {
            DownloadCategory::query()->whereIn('id', $ids)->increment('total');
        }
    }

    private function error(string $message, string $backUrl, int $status): ViewResponse
    {
        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Upload file'),
            'type'          => 'alert-danger',
            'message'       => $message,
            'back_url'      => $backUrl,
            'back_url_name' => __('Back'),
        ], $status);
    }
}
