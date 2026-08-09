<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Exception;
use Intervention\Image\ImageManager;
use Johncms\FileInfo;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Services\DownloadSlugService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Twig\Markup;

final readonly class FilesUploadController
{
    private const DEFAULT_EXTENSIONS = [
        'mp4', 'rar', 'zip', 'pdf', 'nth', 'txt', 'tar', 'gz',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg',
        'thm', 'jad', 'jar', 'cab', 'sis', 'sisx', 'exe', 'msi',
        'apk', 'djvu', 'fb2', 'webm', 'avi', 'mov', 'aac', 'm4a',
    ];

    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private ImageManager $imageManager,
        private DownloadSlugService $slugService,
        private DownloadFilePathService $filePathService,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $category = DownloadCategory::query()->find($id);

        if ($category === null || ! is_dir($category->dir)) {
            return $this->error(__('The directory does not exist'), '/downloads/');
        }

        $isAdmin = $this->currentUser->rights === 4 || $this->currentUser->rights >= 6;
        $canUpload = $isAdmin || ($category->field && $this->currentUser->isValid());

        if (! $canUpload) {
            return $this->error(__('Access forbidden'), $this->categoryPathService->getCategoryUrl($category));
        }

        $allowedExtensions = $category->field
            ? explode(', ', $category->text)
            : self::DEFAULT_EXTENSIONS;

        $this->navChain->add(__('Downloads'), '/downloads/');

        if ($request->getMethod() === 'POST') {
            return $this->handleUpload($request, $id, $category->dir, $allowedExtensions, $isAdmin);
        }

        return new ViewResponse('@downloads/public/upload.twig', [
            'title'      => __('Upload File'),
            'page_title' => __('Upload File'),
            'action_url' => '/downloads/upload/' . $id . '/',
            'cancel_url' => $this->categoryPathService->getCategoryUrl($category),
            'extensions' => implode(', ', $allowedExtensions),
        ]);
    }

    private function handleUpload(Request $request, int $id, string $categoryDir, array $allowedExtensions, bool $isAdmin): ViewResponse
    {
        $uploadedFiles = $request->files->all();
        $uploadUrl = '/downloads/upload/' . $id . '/';

        if (empty($uploadedFiles) || empty($uploadedFiles['fail'])) {
            return $this->error(__('File not attached'), $uploadUrl, __('Repeat'));
        }

        $post = $request->request->all();
        $config = config('johncms');

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile */
        $uploadedFile = $uploadedFiles['fail'];

        $fileInfo = new FileInfo($uploadedFile->getClientOriginalName());
        $ext = strtolower($fileInfo->getExtension());

        $newFileName = isset($post['new_file']) ? trim($post['new_file']) : null;
        if (! empty($newFileName)) {
            $fileInfo = new FileInfo($newFileName . '.' . $ext);
        }

        $fname = $fileInfo->getCleanName();
        $displayName = isset($post['text']) ? trim($post['text']) : null;
        $linkText = isset($post['name_link']) ? mb_substr($post['name_link'], 0, 200) : null;
        $description = isset($post['opis']) ? trim($post['opis']) : null;

        if (empty($displayName)) {
            $displayName = $fname;
        }

        $errors = [];

        if (empty($linkText)) {
            $errors[] = __('The required fields are not filled');
        }

        if ($uploadedFile->getSize() > 1024 * $config['flsz']) {
            $errors[] = __('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
        }

        if (! in_array($ext, $allowedExtensions, true)) {
            $errors[] = new Markup(
                __('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $allowedExtensions),
                'UTF-8'
            );
        }

        if ($errors) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Upload file'),
                'type'          => 'alert-danger',
                'message'       => $errors,
                'back_url'      => $uploadUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        if (file_exists($categoryDir . '/' . $fname)) {
            $fname = time() . $fname;
        }

        try {
            $uploadedFile->move($categoryDir, $fname);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException) {
            return $this->error(__('File not attached'), $uploadUrl, __('Repeat'));
        }

        $moderation = false;
        $type = 2;
        if (! $isAdmin) {
            $moderation = true;
            $type = 3;
        }

        $displayNameTruncated = mb_substr($displayName, 0, 200);
        $slug = $this->slugService->generateUniqueFileSlug($displayNameTruncated, $id);

        $file = DownloadFile::query()->create([
            'refid'    => $id,
            'dir'      => $categoryDir,
            'time'     => time(),
            'name'     => $fname,
            'slug'     => $slug,
            'text'     => $linkText,
            'rus_name' => $displayNameTruncated,
            'type'     => $type,
            'user_id'  => $this->currentUser->id,
            'about'    => $description,
            'desc'     => '',
        ]);

        $screenAttached = null;
        $screenError = null;
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $screenshot */
        $screenshot = $uploadedFiles['screen'] ?? null;
        if ($screenshot !== null) {
            $screensDir = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $file->id;
            if (mkdir($screensDir, 0777, true) || is_dir($screensDir)) {
                try {
                    $img = $this->imageManager->make($screenshot->getPathname());
                    $img->resize(1920, 1080, static function ($constraint): void {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $img->save($screensDir . '/' . $file->id . '.png', 100, 'png');
                    $screenAttached = true;
                } catch (Exception $e) {
                    $screenAttached = false;
                    $screenError = $e->getMessage();
                }
            }
        }

        $viewFileUrl = '';
        if (! $moderation) {
            $viewFileUrl = $this->filePathService->getFileUrlById($file->id) ?? '/downloads/';
            $this->incrementCategoryCounters($id);
        }

        $category = DownloadCategory::query()->find($id);
        $categoryUrl = $category !== null
            ? $this->categoryPathService->getCategoryUrl($category)
            : '/downloads/';

        return new ViewResponse('@downloads/public/upload-result.twig', [
            'title'                 => __('Upload File'),
            'page_title'            => __('Upload File'),
            'id'                    => $id,
            'urls'                  => ['view_file_url' => $viewFileUrl, 'category_url' => $categoryUrl],
            'moderation'            => $moderation,
            'screen_attached'       => $screenAttached,
            'screen_attached_error' => $screenError,
        ]);
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

    private function error(string $message, string $backUrl, ?string $backLabel = null): ViewResponse
    {
        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Upload file'),
            'type'          => 'alert-danger',
            'message'       => $message,
            'back_url'      => $backUrl,
            'back_url_name' => $backLabel ?? __('Back'),
        ]);
    }
}
