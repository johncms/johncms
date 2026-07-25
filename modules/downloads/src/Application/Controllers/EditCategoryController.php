<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadSlugService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditCategoryController
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

    public function __invoke(int $id): Response
    {
        $category = DownloadCategory::query()->find($id);

        if ($category === null || ! is_dir($category->dir)) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Edit Folder'),
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ]), Response::HTTP_NOT_FOUND);
        }

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Edit Folder'));

        $this->render->addData([
            'title'      => __('Edit Folder'),
            'page_title' => __('Edit Folder'),
        ]);

        $do = $this->request->queryParam('do') ?? '';

        if ($do === 'up' || $do === 'down') {
            return $this->handleSort($category, $do);
        }

        if ($this->request->getMethod() === 'POST') {
            return $this->handleSave($id, $category);
        }

        return $this->showForm($id, $category);
    }

    private function handleSort(DownloadCategory $category, string $direction): Response
    {
        $sibling = DownloadCategory::query()
            ->where('refid', $category->refid)
            ->where('sort', $direction === 'up' ? '<' : '>', $category->sort)
            ->orderBy('sort', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($sibling) {
            [$category->sort, $sibling->sort] = [$sibling->sort, $category->sort];
            $category->save();
            $sibling->save();
        }

        $backUrl = $category->refid > 0
            ? ($this->categoryPathService->getCategoryUrlById((int) $category->refid) ?? '/downloads/')
            : '/downloads/';

        return new RedirectResponse($backUrl);
    }

    private function handleSave(int $id, DownloadCategory $category): Response
    {
        $post = $this->request->request->all();
        $rusName = trim($post['rus_name'] ?? '');
        $desc = trim($post['desc'] ?? '');
        $errors = [];

        if (empty($rusName)) {
            $errors[] = __('The required fields are not filled');
        }

        $userDown = 0;
        $format = '';

        if ($this->currentUser->rights === 9 && isset($post['user_down'])) {
            $userDown = 1;
            $format = htmlspecialchars(trim($post['format'] ?? ''));
            foreach (explode(', ', $format) as $value) {
                if (! in_array($value, self::DEFAULT_EXTENSIONS, true)) {
                    $errors[] = __('You can write only the following extensions') . ': ' . implode(', ', self::DEFAULT_EXTENSIONS);
                    break;
                }
            }
        }

        if ($errors) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Edit Folder'),
                'type'          => 'alert-danger',
                'message'       => $errors,
                'back_url'      => '/downloads/categories/' . $id . '/edit',
                'back_url_name' => __('Repeat'),
            ]));
        }

        $slug = $this->slugService->generateUniqueCategorySlug($rusName, (int) $category->refid, $id);

        $category->update([
            'field'    => $userDown,
            'text'     => $format,
            'desc'     => $desc,
            'rus_name' => $rusName,
            'slug'     => $slug,
        ]);

        return new RedirectResponse($this->categoryPathService->getCategoryUrl($category));
    }

    private function showForm(int $id, DownloadCategory $category): Response
    {
        return new Response($this->render->render('downloads::folder_form', [
            'id'            => $id,
            'action_url'    => '/downloads/categories/' . $id . '/edit',
            'cancel_url'    => $this->categoryPathService->getCategoryUrl($category),
            'extensions'    => implode(', ', self::DEFAULT_EXTENSIONS),
            'edit_form'     => true,
            'folder_params' => [
                'name'      => '',
                'rus_name'  => htmlspecialchars($category->rus_name),
                'desc'      => htmlspecialchars($category->desc),
                'user_down' => $category->field,
                'format'    => htmlspecialchars($category->text),
            ],
            'urls'          => ['downloads' => '/downloads/'],
        ]));
    }
}
