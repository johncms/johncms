<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\UseCases\AddForumSectionUseCase;
use Johncms\Modules\Forum\Application\UseCases\DeleteForumSectionUseCase;
use Johncms\Modules\Forum\Application\UseCases\EditForumSectionUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Modules\Forum\Application\Services\ForumSectionTreeService;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;

final readonly class ForumStructureController
{
    private const URL = '/admin/forum/structure';

    public function __construct(
        private NavChain $navChain,
        private ForumSectionTreeService $sectionTree,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ForumStructureRepositoryInterface $repository,
        private AddForumSectionUseCase $addSection,
        private EditForumSectionUseCase $editSection,
        private DeleteForumSectionUseCase $deleteSection,
        private ValidatorInterface $validator,
        private ForumAttachmentStorage $attachments,
    ) {
    }

    public function structure(Request $request): ViewResponse
    {
        $parentId = $request->queryInt('id');

        if ($parentId > 0) {
            $current = $this->repository->find($parentId);
            if ($current === null) {
                redirect(self::URL);
            }
            $title = __('List of sections');
            $sections = $this->repository->subsections($parentId);
            $backUrl = self::URL;
        } else {
            $title = __('List of categories');
            $sections = $this->repository->categories();
            $backUrl = '/admin/forum';
        }

        $this->navChain->add(__('Forum structure'), self::URL);

        $items = $sections->map(fn (ForumSection $section): array => [
            'sort'        => $section->sort,
            'name'        => $section->name,
            'description' => $section->description,
            'counter'     => $section->subsections_count ?? 0,
            'list_url'    => self::URL . '?id=' . $section->id,
            'public_url'  => $section->url,
            'edit_url'    => self::URL . '/' . $section->id . '/edit',
            'delete_url'  => self::URL . '/' . $section->id . '/delete',
        ])->all();

        return new ViewResponse('@forum/admin/structure.twig', $this->menu($title) + [
            'items'        => $items,
            'add_form_url' => self::URL . '/new' . ($parentId ? '?parent=' . $parentId : ''),
            'back_url'     => $backUrl,
        ]);
    }

    /**
     * @param list<string> $errors
     */
    public function addForm(Request $request, array $errors = []): ViewResponse
    {
        $parentId = $request->queryInt('parent');
        $parentName = '';
        if ($parentId > 0) {
            $parent = $this->repository->find($parentId);
            if ($parent === null) {
                return $this->error(__('Invalid ID'));
            }
            $parentName = $parent->name;
        }

        $title = $parentId ? __('Add Section') : __('Add Category');
        $this->navChain->add(__('Forum structure'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@forum/admin/section-form.twig', $this->menu($title) + [
            'parent_id'           => $parentId ?: null,
            'parent_section_name' => $parentName,
            'form_action'         => self::URL . '/new' . ($parentId ? '?parent=' . $parentId : ''),
            'back_url'            => self::URL . ($parentId ? '?id=' . $parentId : ''),
            'field_height'        => $this->currentUser->user()->config->fieldHeight,
            'errors'              => $errors,
            'access_options'      => $this->accessOptions(),
            'type_options'        => $this->sectionTypeOptions(),
        ]);
    }

    public function add(Request $request): ViewResponse
    {
        $parentId = $request->queryInt('parent');
        $name = trim($request->body('name', ''));
        $description = trim($request->body('desc', ''));

        $errors = [];
        if ($name === '') {
            $errors[] = __('You have not entered Title');
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 30) {
            $errors[] = __('Title') . ': ' . __('Invalid length');
        }
        if ($description !== '' && mb_strlen($description) < 2) {
            $errors[] = __('Description should be at least 2 characters in length');
        }
        if ($errors !== []) {
            return $this->addForm($request, $errors);
        }

        $this->addSection->execute(
            $parentId,
            $name,
            $description,
            abs($request->bodyInt('allow')),
            $request->bodyInt('section_type'),
        );

        redirect(self::URL . ($parentId ? '?id=' . $parentId : ''));
    }

    public function editForm(int $id): ViewResponse
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            return $this->error(__('Invalid ID'));
        }

        return $this->renderEditForm($section, $this->fieldsFromSection($section), []);
    }

    public function edit(Request $request, int $id): ViewResponse
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            redirect(self::URL);
        }

        $fields = $this->fieldsFromRequest($request, $section);
        $result = $this->validator->validate(
            ['name' => $fields['name']],
            ['name' => [new StringLength(min: 2, max: 150)]]
        );

        $cycle = $this->editSection->wouldCreateCycle($section->id, (int) $fields['parent']);

        if (! $cycle && $result->isValid()) {
            $this->editSection->execute($section, $fields);
            redirect(self::URL . ($section->parent ? '?id=' . $section->parent : ''));
        }

        // A cycle is a domain failure discovered outside the ruleset, and it joins the result
        // rather than turning it back into a plain array.
        if ($cycle) {
            $result = $result->withError('parent', __('Please select a valid parent'));
        }

        return $this->renderEditForm($section, $fields, $result->getErrors());
    }

    public function deleteConfirm(Request $request, int $id): ViewResponse
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            redirect(self::URL);
        }

        $isTopicSection = (int) $section->section_type === 1;
        $hasChildren = $isTopicSection
            ? $this->repository->countTopics($id) > 0
            : $this->repository->countChildSections($id) > 0;

        $title = ($isTopicSection ? __('Delete section') : __('Delete category')) . ': ' . $section->name;
        $this->navChain->add(__('Forum structure'), self::URL);
        $this->navChain->add($title);

        if (! $hasChildren) {
            return new ViewResponse('@forum/admin/section-delete-confirm.twig', $this->menu($title) + [
                'form_action' => self::URL . '/' . $id . '/delete',
                'back_url'    => self::URL,
            ]);
        }

        if (! $isTopicSection) {
            return new ViewResponse('@forum/admin/section-move-sections.twig', $this->menu($title) + [
                'id'          => $id,
                'categories'  => $this->moveOptions($this->repository->categoriesForMove($id), $section->parent),
                'form_action' => self::URL . '/' . $id . '/delete',
                'back_url'    => self::URL,
                'can_destroy' => $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY),
            ]);
        }

        $ref = $request->queryInt('cat') ?: (int) $section->parent;

        return new ViewResponse('@forum/admin/section-move-topics.twig', $this->menu($title) + [
            'id'          => $id,
            'sections'    => $this->sectionRows($this->repository->sectionsForMove($ref, $id)),
            'categories'  => $this->sectionRows($this->repository->topLevelExcept($ref)),
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::URL,
            'can_destroy' => $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY),
        ]);
    }

    public function delete(Request $request, int $id): ViewResponse
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            redirect(self::URL);
        }

        $parent = (int) $section->parent;
        $isTopicSection = (int) $section->section_type === 1;
        $hasChildren = $isTopicSection
            ? $this->repository->countTopics($id) > 0
            : $this->repository->countChildSections($id) > 0;

        if (! $hasChildren) {
            $this->deleteSection->deleteEmpty($id);
            redirect(self::URL . ($parent ? '?id=' . $parent : ''));
        }

        if (! $isTopicSection) {
            $target = $request->bodyInt('category');
            if ($target <= 0 || $target === $id || $this->repository->find($target) === null) {
                return $this->error(__('Wrong data'));
            }
            $this->deleteSection->moveSubsectionsAndDelete($id, $target);
            redirect(self::URL);
        }

        if ($request->hasBody('delete')) {
            if (! $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY)) {
                return $this->error(__('Access denied'));
            }
            foreach ($this->deleteSection->deleteWithContent($id) as $filename) {
                $this->attachments->delete((string) $filename);
            }
            redirect(self::URL . ($parent ? '?id=' . $parent : ''));
        }

        $target = $request->bodyInt('subcat');
        $targetSection = $this->repository->find($target);
        if ($target <= 0 || $target === $id || $targetSection === null || (int) $targetSection->section_type !== 1) {
            return $this->error(__('Wrong data'));
        }
        $this->deleteSection->moveTopicsAndDelete($id, $target);
        redirect(self::URL);
    }

    private function renderEditForm(ForumSection $section, array $fields, array $errors): ViewResponse
    {
        $title = __('Edit Section');
        $this->navChain->add(__('Forum structure'), self::URL);
        $this->navChain->add($title);

        $categories = [['id' => 0, 'name' => ' - ', 'selected' => empty($section->parent)]];
        foreach ($this->sectionTree->getFlatTree() as $item) {
            $categories[] = [
                'id'       => $item['id'],
                'name'     => $item['name'],
                'selected' => $item['id'] === (int) $section->parent,
            ];
        }

        return new ViewResponse('@forum/admin/section-edit.twig', $this->menu($title) + [
            'item'           => $fields,
            'errors'         => $errors,
            'categories'     => $categories,
            'access_options' => $this->accessOptions(),
            'type_options'   => $this->sectionTypeOptions(),
            'form_action'    => self::URL . '/' . $section->id . '/edit',
            'back_url'       => self::URL . ($section->parent ? '?id=' . $section->parent : ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromSection(ForumSection $section): array
    {
        return [
            'name'             => $section->name,
            'description'      => $section->description,
            'sort'             => $section->sort ?? 100,
            'section_type'     => (int) ($section->section_type ?? 0),
            'parent'           => (int) ($section->parent ?? 0),
            'access'           => (int) ($section->access ?? 0),
            'meta_description' => $section->meta_description ?? '',
            'meta_keywords'    => $section->meta_keywords ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request, ForumSection $section): array
    {
        return [
            'name'             => trim($request->body('name', (string) $section->name)),
            'description'      => trim($request->body('description', (string) $section->description)),
            'sort'             => $request->bodyInt('sort', $section->sort ?? 100),
            'section_type'     => $request->bodyInt('section_type', (int) ($section->section_type ?? 0)),
            'parent'           => $request->bodyInt('parent', (int) ($section->parent ?? 0)),
            'access'           => $request->bodyInt('access', (int) ($section->access ?? 0)),
            'meta_description' => trim($request->body('meta_description', $section->meta_description ?? '')),
            'meta_keywords'    => trim($request->body('meta_keywords', $section->meta_keywords ?? '')),
        ];
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
     * @return list<array{value: int, label: string}>
     */
    private function accessOptions(): array
    {
        return [
            ['value' => 0, 'label' => __('Common access')],
            ['value' => 1, 'label' => __('Assign the newly created authors as curators')],
            ['value' => 2, 'label' => __('Allow authors to edit the 1st post')],
            ['value' => 4, 'label' => __('Only for reading')],
        ];
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function sectionTypeOptions(): array
    {
        return [
            ['value' => 0, 'label' => __('For subsections')],
            ['value' => 1, 'label' => __('For topics')],
        ];
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
        $title = __('Forum structure');

        return new ViewResponse('@admin/pages/result.twig', $this->menu($title) + [
            'type'     => 'alert-danger',
            'message'  => $message,
            'back_url' => self::URL,
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
