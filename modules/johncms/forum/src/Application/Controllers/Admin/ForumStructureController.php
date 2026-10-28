<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\UseCases\DeleteForumSectionUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;

final readonly class ForumStructureController
{
    private const URL = '/admin/forum/structure';

    /** The tree of the forum, where every form returns. */
    private const LIST_URL = '/admin/forum';

    public function __construct(
        private NavChain $navChain,
        private AccessCheckerInterface $accessChecker,
        private ForumStructureRepositoryInterface $repository,
        private DeleteForumSectionUseCase $deleteSection,
        private ForumAttachmentStorage $attachments,
    ) {
    }

    public function deleteConfirm(Request $request, int $id): ViewResponse
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            redirect(self::LIST_URL);
        }

        $isTopicSection = (int) $section->section_type === 1;
        $hasChildren = $isTopicSection
            ? $this->repository->countTopics($id) > 0
            : $this->repository->countChildSections($id) > 0;

        $title = ($isTopicSection ? __('Delete section') : __('Delete category')) . ': ' . $section->name;
        $this->navChain->add(__('Forum'), self::LIST_URL);
        $this->navChain->add($title);

        if (! $hasChildren) {
            return new ViewResponse('@forum/admin/section-delete-confirm.twig', $this->menu($title) + [
                'form_action' => self::URL . '/' . $id . '/delete',
                'back_url'    => self::LIST_URL,
            ]);
        }

        if (! $isTopicSection) {
            return new ViewResponse('@forum/admin/section-move-sections.twig', $this->menu($title) + [
                'id'          => $id,
                'categories'  => $this->moveOptions($this->repository->categoriesForMove($id), $section->parent),
                'form_action' => self::URL . '/' . $id . '/delete',
                'back_url'    => self::LIST_URL,
                'can_destroy' => $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY),
            ]);
        }

        $ref = $request->queryInt('cat') ?: (int) $section->parent;

        return new ViewResponse('@forum/admin/section-move-topics.twig', $this->menu($title) + [
            'id'          => $id,
            'sections'    => $this->sectionRows($this->repository->sectionsForMove($ref, $id)),
            'categories'  => $this->sectionRows($this->repository->topLevelExcept($ref)),
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::LIST_URL,
            'can_destroy' => $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY),
        ]);
    }

    public function delete(Request $request, int $id): ViewResponse
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            redirect(self::LIST_URL);
        }

        $isTopicSection = (int) $section->section_type === 1;
        $hasChildren = $isTopicSection
            ? $this->repository->countTopics($id) > 0
            : $this->repository->countChildSections($id) > 0;

        if (! $hasChildren) {
            $this->deleteSection->deleteEmpty($id);
            redirect(self::LIST_URL);
        }

        if (! $isTopicSection) {
            $target = $request->bodyInt('category');
            if ($target <= 0 || $target === $id || $this->repository->find($target) === null) {
                return $this->error(__('Wrong data'));
            }
            $this->deleteSection->moveSubsectionsAndDelete($id, $target);
            redirect(self::LIST_URL);
        }

        if ($request->hasBody('delete')) {
            if (! $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY)) {
                return $this->error(__('Access denied'));
            }
            foreach ($this->deleteSection->deleteWithContent($id) as $filename) {
                $this->attachments->delete((string) $filename);
            }
            redirect(self::LIST_URL);
        }

        $target = $request->bodyInt('subcat');
        $targetSection = $this->repository->find($target);
        if ($target <= 0 || $target === $id || $targetSection === null || (int) $targetSection->section_type !== 1) {
            return $this->error(__('Wrong data'));
        }
        $this->deleteSection->moveTopicsAndDelete($id, $target);
        redirect(self::LIST_URL);
    }

    /**
     * @param \Illuminate\Support\Collection<int, ForumSection> $sections
     * @return list<array{id: int, name: string, selected: bool}>
     */
    private function moveOptions(\Illuminate\Support\Collection $sections, mixed $currentParent): array
    {
        return $sections->map(fn (ForumSection $section): array => [
            'id'       => $section->id,
            'name'     => $section->name,
            'selected' => $section->id === (int) $currentParent,
        ])->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int, ForumSection> $sections
     * @return list<array{id: int, name: string}>
     */
    private function sectionRows(\Illuminate\Support\Collection $sections): array
    {
        return $sections->map(fn (ForumSection $section): array => [
            'id'   => $section->id,
            'name' => $section->name,
        ])->all();
    }

    private function error(string $message): ViewResponse
    {
        $title = __('Forum');

        return new ViewResponse('@admin/pages/result.twig', $this->menu($title) + [
            'type'     => 'alert-danger',
            'message'  => $message,
            'back_url' => self::LIST_URL,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['forum' => true],
        ];
    }
}
