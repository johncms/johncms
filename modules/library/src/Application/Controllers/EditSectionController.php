<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Application\Services\LibrarySlugService;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditSectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private User $currentUser,
        private LibrarySlugService $slugService,
        private LibraryCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        if (! ($this->currentUser->rights > 4)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Edit Section'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $category = LibraryCategory::query()->find($id);

        if ($category === null) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Edit Section'),
                    'type'    => 'alert-danger',
                    'message' => __('Section does not exist'),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add(__('Edit Section'));

        $pageData = [
            'title'      => __('Edit Section'),
            'page_title' => __('Edit Section'),
            'id'         => $id,
        ];

        if ($request->getMethod() === 'POST') {
            $this->save($request, $id, $category);

            return new ViewResponse('@library/public/edit-section.twig', $pageData + [
                'category_url' => $category->url,
                'saved'        => true,
            ]);
        }

        $isEmpty = ! LibraryCategory::query()->where('parent', $id)->exists()
            && ! LibraryText::query()->where('cat_id', $id)->exists();

        $parentSections = [];
        foreach ($this->getParentSections($id) as $section) {
            $parentSections[] = ['id' => (int) $section->id, 'name' => $section->name];
        }

        return new ViewResponse('@library/public/edit-section.twig', $pageData + [
            'category_url'    => $category->url,
            'saved'           => false,
            'name'            => $category->name,
            'description'     => (string) ($category->description ?? ''),
            'dir'             => (int) $category->dir,
            'user_add'        => (int) $category->user_add,
            'parent'          => (int) $category->parent,
            'is_empty'        => $isEmpty,
            'parent_sections' => $parentSections,
            'field_height'    => $this->currentUser->config->fieldHeight,
        ]);
    }

    private function save(Request $request, int $id, LibraryCategory $category): void
    {
        $post = $request->request->all();

        $newName     = mb_substr(trim((string) ($post['name'] ?? '')), 0, 100);
        $newParentId = isset($post['move']) && LibraryCategory::query()->count() > 1
            ? (int) $post['move']
            : (int) $category->parent;

        $fields = [
            'name'        => $newName,
            'description' => trim((string) ($post['description'] ?? '')),
        ];

        $nameChanged   = $newName !== $category->name;
        $parentChanged = $newParentId !== (int) $category->parent;

        if ($nameChanged || $parentChanged) {
            $fields['slug'] = $this->slugService->generateCategorySlug($newName, $newParentId, $id);
        }

        $isEmpty = ! LibraryCategory::query()->where('parent', $id)->exists()
            && ! LibraryText::query()->where('cat_id', $id)->exists();

        if ($isEmpty && isset($post['dir'])) {
            $fields['dir'] = (int) $post['dir'];
        }

        if ((int) $category->dir === 0 && isset($post['user_add'])) {
            $fields['user_add'] = (int) $post['user_add'];
        }

        if ($parentChanged) {
            $fields['parent'] = $newParentId;
        }

        $category->update($fields);
    }

    /** @return Collection<int, LibraryCategory> */
    private function getParentSections(int $id): Collection
    {
        $childTree = new Tree($id);
        $childIds  = $childTree->getChildsDir()->result();

        return LibraryCategory::query()
            ->select(['id', 'name', 'parent'])
            ->where('dir', 1)
            ->where('id', '!=', $id)
            ->when(count($childIds) > 0, fn ($q) => $q->whereNotIn('id', array_values($childIds)))
            ->get();
    }
}
