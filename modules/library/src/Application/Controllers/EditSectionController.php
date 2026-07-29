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
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditSectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private LibrarySlugService $slugService,
        private LibraryCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request, int $id): Response
    {
        if (! ($this->currentUser->rights > 4)) {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'   => __('Edit Section'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        $category = LibraryCategory::query()->find($id);

        if ($category === null) {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'   => __('Edit Section'),
                    'type'    => 'alert-danger',
                    'message' => __('Section does not exist'),
                ]),
                Response::HTTP_NOT_FOUND
            );
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add(__('Edit Section'));

        $this->render->addData([
            'title'      => __('Edit Section'),
            'page_title' => __('Edit Section'),
        ]);

        if ($request->getMethod() === 'POST') {
            $this->save($request, $id, $category);
            return new Response($this->render->render('library::edit_section', [
                'id'           => $id,
                'category_url' => $category->url,
                'saved'        => true,
            ]));
        }

        $isEmpty = ! LibraryCategory::query()->where('parent', $id)->exists()
            && ! LibraryText::query()->where('cat_id', $id)->exists();

        $parentSections = $this->getParentSections($id);

        return new Response($this->render->render('library::edit_section', [
            'id'             => $id,
            'category_url'   => $category->url,
            'category'       => $category,
            'isEmpty'        => $isEmpty,
            'parentSections' => $parentSections,
            'saved'          => false,
        ]));
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
