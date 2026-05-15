<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;

final readonly class EditSectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id): string
    {
        if (! ($this->currentUser->rights > 4)) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'   => __('Edit Section'),
                'type'    => 'alert-danger',
                'message' => __('Access forbidden'),
            ]);
        }

        $category = LibraryCategory::query()->find($id);

        if ($category === null) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'   => __('Edit Section'),
                'type'    => 'alert-danger',
                'message' => __('Section does not exist'),
            ]);
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

        if ($this->request->getMethod() === 'POST') {
            $this->save($id, $category);
            return $this->render->render('library::edit_section', [
                'id'    => $id,
                'saved' => true,
            ]);
        }

        $isEmpty = ! LibraryCategory::query()->where('parent', $id)->exists()
            && ! LibraryText::query()->where('cat_id', $id)->exists();

        $parentSections = $this->getParentSections($id);

        return $this->render->render('library::edit_section', [
            'id'             => $id,
            'category'       => $category,
            'isEmpty'        => $isEmpty,
            'parentSections' => $parentSections,
            'saved'          => false,
        ]);
    }

    private function save(int $id, LibraryCategory $category): void
    {
        $post = $this->request->getParsedBody();

        $fields = [
            'name'        => mb_substr(trim((string) ($post['name'] ?? '')), 0, 100),
            'description' => trim((string) ($post['description'] ?? '')),
        ];

        $isEmpty = ! LibraryCategory::query()->where('parent', $id)->exists()
            && ! LibraryText::query()->where('cat_id', $id)->exists();

        if ($isEmpty && isset($post['dir'])) {
            $fields['dir'] = (int) $post['dir'];
        }

        if ((int) $category->dir === 0 && isset($post['user_add'])) {
            $fields['user_add'] = (int) $post['user_add'];
        }

        if (isset($post['move']) && LibraryCategory::query()->count() > 1) {
            $fields['parent'] = (int) $post['move'];
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
