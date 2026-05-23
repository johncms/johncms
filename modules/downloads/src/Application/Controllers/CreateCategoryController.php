<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadSlugService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class CreateCategoryController
{
    private const DEFAULT_EXTENSIONS = [
        'mp4', 'rar', 'zip', 'pdf', 'nth', 'txt', 'tar', 'gz',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg',
        'thm', 'jad', 'jar', 'cab', 'sis', 'sisx', 'exe', 'msi',
        'apk', 'djvu', 'fb2', 'webm', 'avi', 'mov', 'aac', 'm4a',
    ];

    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private DownloadSlugService $slugService,
        private DownloadCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        $refid = max(0, (int) $this->request->getQuery('refid', 0));

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Create Folder'));

        $this->render->addData([
            'title'      => __('Create Folder'),
            'page_title' => __('Create Folder'),
        ]);

        $parentCategory = null;
        if ($refid > 0) {
            $parentCategory = DownloadCategory::query()->find($refid);
            if ($parentCategory === null || ! is_dir($parentCategory->dir)) {
                http_response_code(404);
                return $this->render->render('system::pages/result', [
                    'title'         => __('Create Folder'),
                    'type'          => 'alert-danger',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]);
            }
            $baseDir = $parentCategory->dir;
        } else {
            $baseDir = 'upload/downloads/files';
        }

        $cancelUrl = $parentCategory !== null
            ? $this->categoryPathService->getCategoryUrl($parentCategory)
            : '/downloads/';

        $baseUrl = '/downloads/categories/create' . ($refid ? '?refid=' . $refid : '');

        if ($this->request->getMethod() === 'POST') {
            return $this->handleCreate($refid, $baseDir, $baseUrl);
        }

        return $this->render->render('downloads::folder_form', [
            'id'            => $refid,
            'action_url'    => $baseUrl,
            'cancel_url'    => $cancelUrl,
            'extensions'    => implode(', ', self::DEFAULT_EXTENSIONS),
            'edit_form'     => false,
            'folder_params' => ['name' => '', 'rus_name' => '', 'desc' => '', 'user_down' => '', 'format' => ''],
            'urls'          => ['downloads' => '/downloads/'],
        ]);
    }

    private function handleCreate(int $refid, string $baseDir, string $baseUrl): string
    {
        $post = $this->request->getParsedBody();
        $name = trim($post['name'] ?? '');
        $rusName = trim($post['rus_name'] ?? '');
        $desc = trim($post['desc'] ?? '');
        $userDown = isset($post['user_down']) ? 1 : 0;
        $format = ($userDown && isset($post['format'])) ? trim($post['format']) : '';
        $errors = [];

        if (empty($name)) {
            $errors[] = __('The required fields are not filled');
        }

        if (preg_match('/[^0-9a-zA-Z]+/', $name)) {
            $errors[] = __('Invalid characters');
        }

        if ($this->currentUser->rights === 9 && $userDown && $format) {
            foreach (explode(',', $format) as $value) {
                if (! in_array(trim($value), self::DEFAULT_EXTENSIONS, true)) {
                    $errors[] = __('You can write only the following extensions') . ': ' . implode(', ', self::DEFAULT_EXTENSIONS);
                    break;
                }
            }
        }

        if ($errors) {
            return $this->render->render('system::pages/result', [
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
            return $this->render->render('system::pages/result', [
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

        return $this->render->render('system::pages/result', [
            'title'         => __('Create Folder'),
            'type'          => 'alert-success',
            'message'       => __('The Folder is created'),
            'back_url'      => $this->categoryPathService->getCategoryUrl($category),
            'back_url_name' => __('Continue'),
        ]);
    }
}
