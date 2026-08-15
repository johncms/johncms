<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadSlugService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class CreateCategoryController
{
    private const DEFAULT_EXTENSIONS = [
        'mp4', 'rar', 'zip', 'pdf', 'nth', 'txt', 'tar', 'gz',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg',
        'thm', 'jad', 'jar', 'cab', 'sis', 'sisx', 'exe', 'msi',
        'apk', 'djvu', 'fb2', 'webm', 'avi', 'mov', 'aac', 'm4a',
    ];

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private DownloadSlugService $slugService,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $refid = max(0, $request->queryInt('refid', 0));

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Create Folder'));

        $parentCategory = null;
        if ($refid > 0) {
            $parentCategory = DownloadCategory::query()->find($refid);
            if ($parentCategory === null || ! is_dir($parentCategory->dir)) {
                return new ViewResponse('@theme/pages/result.twig', [
                    'title'         => __('Create Folder'),
                    'type'          => 'alert-danger',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ], Response::HTTP_NOT_FOUND);
            }
            $baseDir = $parentCategory->dir;
        } else {
            $baseDir = 'upload/downloads/files';
        }

        $cancelUrl = $parentCategory !== null
            ? $this->categoryPathService->getCategoryUrl($parentCategory)
            : '/downloads/';

        $baseUrl = '/downloads/categories/create' . ($refid ? '?refid=' . $refid : '');

        if ($request->getMethod() === 'POST') {
            return $this->handleCreate($request, $refid, $baseDir, $baseUrl);
        }

        return new ViewResponse('@downloads/public/category-form.twig', [
            'title'         => __('Create Folder'),
            'page_title'    => __('Create Folder'),
            'action_url'    => $baseUrl,
            'cancel_url'    => $cancelUrl,
            'extensions'    => implode(', ', self::DEFAULT_EXTENSIONS),
            'edit_form'     => false,
            'can_set_rules' => $this->accessChecker->allows(DownloadsPermissions::UPLOAD_RULES_MANAGE),
            'folder_params' => ['name' => '', 'rus_name' => '', 'desc' => '', 'user_down' => 0, 'format' => ''],
        ]);
    }

    private function handleCreate(Request $request, int $refid, string $baseDir, string $baseUrl): ViewResponse
    {
        $post = $request->request->all();
        $name = trim($post['name'] ?? '');
        $rusName = trim($post['rus_name'] ?? '');
        $desc = trim($post['desc'] ?? '');
        // Who may upload into the folder is set by whoever is allowed to decide it; the form
        // does not offer the field to anybody else, and a request that carries it anyway is not
        // a reason to open the folder up.
        $canSetRules = $this->accessChecker->allows(DownloadsPermissions::UPLOAD_RULES_MANAGE);
        $userDown = ($canSetRules && isset($post['user_down'])) ? 1 : 0;
        $format = ($userDown && isset($post['format'])) ? trim($post['format']) : '';
        $errors = [];

        if (empty($name)) {
            $errors[] = __('The required fields are not filled');
        }

        if (preg_match('/[^0-9a-zA-Z]+/', $name)) {
            $errors[] = __('Invalid characters');
        }

        if ($userDown && $format) {
            foreach (explode(',', $format) as $value) {
                if (! in_array(trim($value), self::DEFAULT_EXTENSIONS, true)) {
                    $errors[] = __('You can write only the following extensions') . ': ' . implode(', ', self::DEFAULT_EXTENSIONS);
                    break;
                }
            }
        }

        if ($errors) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Create Folder'),
                'type'          => 'alert-danger',
                'message'       => $errors,
                'back_url'      => $baseUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        if (empty($rusName)) {
            $rusName = $name;
        }

        $dir = $baseDir . \DS . $name;

        if (is_dir($dir) || ! mkdir($dir, 0777)) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Create Folder'),
                'type'          => 'alert-danger',
                'message'       => __('Error creating categories'),
                'back_url'      => $baseUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        chmod($dir, 0777);

        $slug = $this->slugService->generateUniqueCategorySlug($rusName, $refid);

        $category = DownloadCategory::query()->create([
            'refid'    => $refid,
            'dir'      => $dir,
            'sort'     => time(),
            'name'     => $name,
            'slug'     => $slug,
            'desc'     => $desc,
            'field'    => $userDown,
            'text'     => $format,
            'rus_name' => $rusName,
        ]);

        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Create Folder'),
            'type'          => 'alert-success',
            'message'       => __('The Folder is created'),
            'back_url'      => $this->categoryPathService->getCategoryUrl($category),
            'back_url_name' => __('Continue'),
        ]);
    }
}
