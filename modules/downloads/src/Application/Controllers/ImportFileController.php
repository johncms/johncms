<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Exception;
use Intervention\Image\ImageManager;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Markup;

final readonly class ImportFileController
{
    private const DEFAULT_EXTENSIONS = [
        'mp4', 'rar', 'zip', 'pdf', 'nth', 'txt', 'tar', 'gz',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg',
        'thm', 'jad', 'jar', 'cab', 'sis', 'sisx', 'exe', 'msi',
        'apk', 'djvu', 'fb2', 'webm', 'avi', 'mov', 'aac', 'm4a',
    ];

    public function __construct(
        private CurrentUser $currentUser,
        private ImageManager $imageManager,
        private DownloadFilePathService $filePathService,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $category = DownloadCategory::query()->find($id);

        if ($category === null || ! is_dir($category->dir)) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Error'),
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ], Response::HTTP_NOT_FOUND);
        }

        $allowedExtensions = $category->field ? explode(', ', $category->text) : self::DEFAULT_EXTENSIONS;

        $baseUrl = '/downloads/import/' . $id . '/';

        if ($request->getMethod() === 'POST') {
            return $this->handleImport($request, $id, $category, $allowedExtensions, $baseUrl);
        }

        return new ViewResponse('@downloads/public/import.twig', [
            'title'      => __('File import'),
            'page_title' => __('File import'),
            'action_url' => $baseUrl,
            'cancel_url' => $this->categoryPathService->getCategoryUrl($category),
            'extensions' => implode(', ', $allowedExtensions),
        ]);
    }

    private function handleImport(Request $request, int $id, DownloadCategory $category, array $allowedExtensions, string $baseUrl): ViewResponse
    {
        $post = $request->request->all();
        $errors = [];

        $url = isset($post['url']) ? trim($post['url']) : null;
        $scheme = $url ? parse_url($url, PHP_URL_SCHEME) : null;

        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            $errors[] = __('Invalid Link');
        } else {
            $host = gethostbyname((string) parse_url($url, PHP_URL_HOST));
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $errors[] = __('Invalid Link');
            }
        }

        if ($errors) {
            return $this->renderErrors($errors, $baseUrl);
        }

        $filename = basename((string) parse_url($url, PHP_URL_PATH));
        $customFilename = isset($post['filename']) ? trim($post['filename']) : null;
        $displayName = isset($post['text']) ? trim($post['text']) : null;
        $linkText = isset($post['link_text']) ? mb_substr($post['link_text'], 0, 200) : null;
        $description = isset($post['description']) ? trim($post['description']) : null;

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (! empty($customFilename)) {
            $filename = strtolower($customFilename . '.' . $extension);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        }

        if (empty($displayName)) {
            $displayName = $filename;
        }

        if (empty($linkText)) {
            $errors[] = __('The required fields are not filled');
        }

        if (! in_array($extension, $allowedExtensions, true)) {
            $errors[] = new Markup(
                __('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $allowedExtensions),
                'UTF-8'
            );
        }

        if (strlen($filename) > 100) {
            $errors[] = __('The file name length must not exceed 100 characters');
        }

        if (preg_match('/[^\da-zA-Z_\-.]+/', $filename)) {
            $errors[] = __('The file name contains invalid characters');
        }

        if ($errors) {
            return $this->renderErrors($errors, $baseUrl);
        }

        if (file_exists($category->dir . '/' . $filename)) {
            $filename = time() . $filename;
        }

        if (! copy($url, $category->dir . '/' . $filename)) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('File import'),
                'type'          => 'alert-danger',
                'message'       => __('File not attached'),
                'back_url'      => $baseUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        $file = DownloadFile::query()->create([
            'refid'    => $id,
            'dir'      => $category->dir,
            'time'     => time(),
            'name'     => $filename,
            'text'     => $linkText,
            'rus_name' => mb_substr($displayName, 0, 200),
            'type'     => 2,
            'user_id'  => $this->currentUser->id(),
            'about'    => $description,
            'desc'     => '',
        ]);

        $screenAttached = null;
        $screenError = null;
        $files = $request->files->all();
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $screenshot */
        $screenshot = $files['screen'] ?? null;
        if ($screenshot !== null && ! $screenshot->getError()) {
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

        $this->incrementCategoryCounters($id);

        return new ViewResponse('@downloads/public/import-result.twig', [
            'title'                 => __('File import'),
            'page_title'            => __('File import'),
            'id'                    => $id,
            'urls'                  => [
                'view_file_url' => $this->filePathService->getFileUrl($file),
                'category_url'  => $this->categoryPathService->getCategoryUrl($category),
            ],
            'screen_attached'       => $screenAttached,
            'screen_attached_error' => $screenError,
        ]);
    }

    private function incrementCategoryCounters(int $categoryId): void
    {
        $ids = [];
        $dirid = $categoryId;
        while ($dirid !== 0) {
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

    private function renderErrors(array $errors, string $backUrl): ViewResponse
    {
        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('File import'),
            'type'          => 'alert-danger',
            'message'       => $errors,
            'back_url'      => $backUrl,
            'back_url_name' => __('Repeat'),
        ]);
    }
}
