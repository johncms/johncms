<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteSectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        if (! ($this->currentUser->rights > 4)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Delete'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $category = LibraryCategory::query()->find($id);

        if ($category === null) {
            return new ViewResponse('@theme/pages/result.twig', [
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

        $hasChildren = LibraryCategory::query()->where('parent', $id)->exists()
            || LibraryText::query()->where('cat_id', $id)->exists();

        if (! $hasChildren) {
            return $this->handleEmptySection($request, $id, $category->name);
        }

        return $this->handleNonEmptySection($request, $id, $category->name, (bool) $category->dir);
    }

    private function handleEmptySection(Request $request, int $id, string $name): ViewResponse
    {
        $deleted = false;

        if ($request->query->has('yes')) {
            LibraryCategory::query()->where('id', $id)->delete();
            $deleted = true;
        }

        return new ViewResponse('@library/public/delete-section.twig', $this->pageData() + [
            'id'       => $id,
            'name'     => $name,
            'is_empty' => true,
            'deleted'  => $deleted,
        ]);
    }

    private function handleNonEmptySection(Request $request, int $id, string $name, bool $isDir): ViewResponse
    {
        $post = $request->request->all();
        $mode = (string) ($post['mode'] ?? $request->queryParam('do', ''));

        $moved = false;
        $moveTarget = null;
        $moveSections = null;
        $pendingMove = null;
        $deletedCounts = null;

        switch ($mode) {
            case 'moveaction':
                if ($request->query->has('movedeny')) {
                    $move = $request->queryInt('move', 0);
                    if ($isDir) {
                        LibraryCategory::query()->where('parent', $id)->update(['parent' => $move]);
                    } else {
                        LibraryText::query()->where('cat_id', $id)->update(['cat_id' => $move]);
                    }
                    LibraryCategory::query()->where('id', $id)->delete();
                    $moved = true;
                    $moveTarget = $move;
                } else {
                    $pendingMove = (int) ($post['move'] ?? 0);
                }
                break;

            case 'delmove':
                $moveSections = $this->getMoveTargetSections($id, $isDir);
                break;

            case 'delall':
                if ($request->query->has('deldeny')) {
                    $childs = new Tree($id);
                    $deletedCounts = $childs->getAllChildsId()->cleanDir();
                }
                break;
        }

        return new ViewResponse('@library/public/delete-section.twig', $this->pageData() + [
            'id'             => $id,
            'name'           => $name,
            'is_empty'       => false,
            'deleted'        => false,
            'mode'           => $mode,
            'moved'          => $moved,
            'move_target'    => $moveTarget,
            'move_sections'  => $moveSections,
            'pending_move'   => $pendingMove,
            'deleted_counts' => $deletedCounts,
        ]);
    }

    /** @return array<string, string> */
    private function pageData(): array
    {
        return [
            'title'      => __('Delete Section'),
            'page_title' => __('Delete Section'),
        ];
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
