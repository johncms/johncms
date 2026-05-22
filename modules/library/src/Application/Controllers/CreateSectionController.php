<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Application\Services\LibrarySlugService;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class CreateSectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private User $currentUser,
        private LibrarySlugService $slugService,
        private LibraryCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        if ($this->currentUser->rights <= 4) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'         => __('Create Section'),
                'type'          => 'alert-danger',
                'message'       => __('Access denied'),
                'back_url'      => '/library/',
                'back_url_name' => __('Library'),
            ]);
        }

        $parentId = max(0, (int) $this->request->getQuery('id', 0));
        $formUrl = '/library/section/create' . ($parentId ? '?id=' . $parentId : '');

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Create Section'));

        $this->render->addData([
            'title'      => __('Create Section'),
            'page_title' => __('Create Section'),
        ]);

        if ($this->request->getMethod() === 'POST') {
            return $this->handlePost($parentId, $formUrl);
        }

        return $this->renderForm($parentId, $formUrl, false);
    }

    private function handlePost(int $parentId, string $formUrl): string
    {
        $post = $this->request->getParsedBody();
        $name = trim((string) ($post['name'] ?? ''));

        if (empty($name)) {
            return $this->render->render('system::pages/result', [
                'title'         => __('Create Section'),
                'type'          => 'alert-danger',
                'message'       => __('You have not entered the name'),
                'back_url'      => $formUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        $pos  = (int) LibraryCategory::query()->max('id') + 1;
        $slug = $this->slugService->generateCategorySlug($name, $parentId);

        LibraryCategory::query()->create([
            'parent'      => $parentId,
            'name'        => $name,
            'slug'        => $slug,
            'description' => trim((string) ($post['description'] ?? '')),
            'dir'         => (int) ($post['type'] ?? 0),
            'pos'         => $pos,
        ]);

        return $this->renderForm($parentId, $formUrl, true);
    }

    private function renderForm(int $parentId, string $formUrl, bool $created): string
    {
        $parentUrl = $parentId > 0
            ? $this->categoryPathService->getCategoryUrlById($parentId) ?? '/library/'
            : '/library/';

        return $this->render->render('library::section_create', [
            'form_url'   => $formUrl,
            'parent_id'  => $parentId,
            'parent_url' => $parentUrl,
            'created'    => $created,
        ]);
    }
}
