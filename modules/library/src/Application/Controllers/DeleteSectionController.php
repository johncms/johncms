<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Library\Tree;

final readonly class DeleteSectionController
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
                'title'   => __('Delete'),
                'type'    => 'alert-danger',
                'message' => __('Access forbidden'),
            ]);
        }

        $category = LibraryCategory::query()->find($id);

        if ($category === null) {
            return $this->render->render('system::pages/result', [
                'title'   => __('Delete'),
                'type'    => 'alert-danger',
                'message' => __('Section does not exist'),
            ]);
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add(__('Delete Section'));

        $this->render->addData([
            'title'      => __('Delete Section'),
            'page_title' => __('Delete Section'),
        ]);

        $hasChildren = LibraryCategory::query()->where('parent', $id)->exists()
            || LibraryText::query()->where('cat_id', $id)->exists();

        if (! $hasChildren) {
            return $this->handleEmptySection($id, $category->name);
        }

        return $this->handleNonEmptySection($id, $category->name, (bool) $category->dir);
    }

    private function handleEmptySection(int $id, string $name): string
    {
        $deleted = false;

        if ($this->request->getQuery('yes', null) !== null) {
            LibraryCategory::query()->where('id', $id)->delete();
            $deleted = true;
        }

        return $this->render->render('library::delete_section', [
            'id'              => $id,
            'name'            => $name,
            'isEmpty'         => true,
            'deleted'         => $deleted,
            'mode'            => null,
            'moving'          => false,
            'moveTarget'      => null,
            'moveSections'    => null,
            'pendingMove'     => null,
            'deleteAllResult' => null,
        ]);
    }

    private function handleNonEmptySection(int $id, string $name, bool $isDir): string
    {
        $post = $this->request->getParsedBody();
        $mode = (string) ($post['mode'] ?? $this->request->getQuery('do', ''));

        $moving = false;
        $moveTarget = null;
        $moveSections = null;
        $pendingMove = null;
        $deleteAllResult = null;

        switch ($mode) {
            case 'moveaction':
                if ($this->request->getQuery('movedeny', null) !== null) {
                    $move = (int) $this->request->getQuery('move', 0);
                    if ($isDir) {
                        LibraryCategory::query()->where('parent', $id)->update(['parent' => $move]);
                    } else {
                        LibraryText::query()->where('cat_id', $id)->update(['cat_id' => $move]);
                    }
                    LibraryCategory::query()->where('id', $id)->delete();
                    $moving = true;
                    $moveTarget = $move;
                } else {
                    $pendingMove = (int) ($post['move'] ?? 0);
                }
                break;

            case 'delmove':
                $moveSections = $this->getMoveTargetSections($id, $isDir);
                break;

            case 'delall':
                if ($this->request->getQuery('deldeny', null) !== null) {
                    $childs = new Tree($id);
                    $counts = $childs->getAllChildsId()->cleanDir();
                    $deleteAllResult = sprintf(
                        __('Successfully deleted:<br>Directories: (%d)<br>Articles: (%d)<br>Tags: (%d)<br>Comments: (%d)<br>Images: (%d)'),
                        $counts['dirs'],
                        $counts['texts'],
                        $counts['tags'],
                        $counts['comments'],
                        $counts['images'],
                    );
                }
                break;
        }

        return $this->render->render('library::delete_section', [
            'id'              => $id,
            'name'            => $name,
            'isEmpty'         => false,
            'deleted'         => false,
            'mode'            => $mode,
            'moving'          => $moving,
            'moveTarget'      => $moveTarget,
            'moveSections'    => $moveSections,
            'pendingMove'     => $pendingMove,
            'deleteAllResult' => $deleteAllResult,
        ]);
    }

    /** @return array<int, string>|null */
    private function getMoveTargetSections(int $id, bool $isDir): ?array
    {
        $childTree = new Tree($id);
        $childIds = $childTree->getChildsDir()->result();

        $query = LibraryCategory::query()
            ->select(['id', 'name'])
            ->where('dir', (int) $isDir);

        if ($isDir && count($childIds) > 0) {
            $query->whereNotIn('id', array_merge(array_values($childIds), [$id]));
        } else {
            $query->where('id', '!=', $id);
        }

        $sections = $query->get();

        if ($sections->isEmpty()) {
            return null;
        }

        return $sections->pluck('name', 'id')->all();
    }
}
