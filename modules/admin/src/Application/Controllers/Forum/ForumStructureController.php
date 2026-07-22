<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Forum;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\AddForumSectionUseCase;
use Johncms\Modules\Admin\Application\UseCases\DeleteForumSectionUseCase;
use Johncms\Modules\Admin\Application\UseCases\EditForumSectionUseCase;
use Johncms\Modules\Admin\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Modules\Forum\Application\Services\ForumSectionTreeService;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class ForumStructureController
{
    private const URL = '/admin/forum/structure';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ForumSectionTreeService $sectionTree,
        private User $currentUser,
        private ForumStructureRepositoryInterface $repository,
        private AddForumSectionUseCase $addSection,
        private EditForumSectionUseCase $editSection,
        private DeleteForumSectionUseCase $deleteSection,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function structure(): string
    {
        $parentId = (int) $this->request->getQuery('id', 0, FILTER_VALIDATE_INT);

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
        $this->render->addData($this->menu($title));

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

        return $this->render->render('admin::forum/structure', [
            'items'        => $items,
            'add_form_url' => self::URL . '/new' . ($parentId ? '?parent=' . $parentId : ''),
            'back_url'     => $backUrl,
        ]);
    }

    public function addForm(?string $error = null): string
    {
        $parentId = (int) $this->request->getQuery('parent', 0, FILTER_VALIDATE_INT);
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
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::forum/add', [
            'parent_id'           => $parentId ?: null,
            'parent_section_name' => $parentName,
            'form_action'         => self::URL . '/new' . ($parentId ? '?parent=' . $parentId : ''),
            'back_url'            => self::URL . ($parentId ? '?id=' . $parentId : ''),
            'field_height'        => $this->currentUser->config->fieldHeight,
            'error_message'       => $error,
        ]);
    }

    public function add(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->addForm(__('Wrong data'));
        }

        $parentId = (int) $this->request->getQuery('parent', 0, FILTER_VALIDATE_INT);
        $name = trim((string) $this->request->getPost('name', ''));
        $description = trim((string) $this->request->getPost('desc', ''));

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
            return $this->addForm(implode('<br>', $errors));
        }

        $this->addSection->execute(
            $parentId,
            $name,
            $description,
            abs((int) $this->request->getPost('allow', 0, FILTER_VALIDATE_INT)),
            (int) $this->request->getPost('section_type', 0, FILTER_VALIDATE_INT),
        );

        redirect(self::URL . ($parentId ? '?id=' . $parentId : ''));
    }

    public function editForm(int $id): string
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            return $this->error(__('Invalid ID'));
        }

        return $this->renderEditForm($section, $this->fieldsFromSection($section), []);
    }

    public function edit(int $id): string
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            redirect(self::URL);
        }

        $fields = $this->fieldsFromRequest($section);
        $validator = new Validator(
            ['name' => $fields['name'], 'csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['name' => ['NotEmpty', 'StringLength' => ['min' => 2, 'max' => 150]], 'csrf_token' => ['Csrf']]
        );

        $cycle = $this->editSection->wouldCreateCycle($section->id, (int) $fields['parent']);

        if (! $cycle && $validator->isValid()) {
            $this->editSection->execute($section, $fields);
            redirect(self::URL . ($section->parent ? '?id=' . $section->parent : ''));
        }

        $errors = $validator->getErrors();
        if ($cycle) {
            $errors['parent'][] = __('Please select a valid parent');
        }

        return $this->renderEditForm($section, $fields, $errors);
    }

    public function deleteConfirm(int $id): string
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
        $this->render->addData($this->menu($title));

        if (! $hasChildren) {
            return $this->render->render('admin::forum/del_confirm', [
                'form_action' => self::URL . '/' . $id . '/delete',
                'back_url'    => self::URL,
            ]);
        }

        if (! $isTopicSection) {
            return $this->render->render('admin::forum/del_confirm_move', [
                'id'          => $id,
                'categories'  => $this->moveOptions($this->repository->categoriesForMove($id), $section->parent),
                'form_action' => self::URL . '/' . $id . '/delete',
                'back_url'    => self::URL,
                'can_destroy' => $this->currentUser->rights === 9,
            ]);
        }

        $ref = (int) $this->request->getQuery('cat', 0, FILTER_VALIDATE_INT) ?: (int) $section->parent;

        return $this->render->render('admin::forum/del_confirm_move_topics', [
            'id'          => $id,
            'sections'    => $this->repository->sectionsForMove($ref, $id),
            'categories'  => $this->repository->topLevelExcept($ref),
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::URL,
            'can_destroy' => $this->currentUser->rights === 9,
        ]);
    }

    public function delete(int $id): string
    {
        if (! $this->isCsrfValid()) {
            return $this->error(__('Wrong data'));
        }

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
            $target = (int) $this->request->getPost('category', 0, FILTER_VALIDATE_INT);
            if ($target <= 0 || $target === $id || $this->repository->find($target) === null) {
                return $this->error(__('Wrong data'));
            }
            $this->deleteSection->moveSubsectionsAndDelete($id, $target);
            redirect(self::URL);
        }

        if ($this->request->getPost('delete') !== null) {
            if ($this->currentUser->rights !== 9) {
                return $this->error(__('Access denied'));
            }
            foreach ($this->deleteSection->deleteWithContent($id) as $filename) {
                @unlink(UPLOAD_PATH . 'forum/attach/' . $filename);
            }
            redirect(self::URL . ($parent ? '?id=' . $parent : ''));
        }

        $target = (int) $this->request->getPost('subcat', 0, FILTER_VALIDATE_INT);
        $targetSection = $this->repository->find($target);
        if ($target <= 0 || $target === $id || $targetSection === null || (int) $targetSection->section_type !== 1) {
            return $this->error(__('Wrong data'));
        }
        $this->deleteSection->moveTopicsAndDelete($id, $target);
        redirect(self::URL);
    }

    private function renderEditForm(ForumSection $section, array $fields, array $errors): string
    {
        $title = __('Edit Section');
        $this->navChain->add(__('Forum structure'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menu($title));

        $categories = [['id' => 0, 'name' => ' - ', 'selected' => empty($section->parent)]];
        foreach ($this->sectionTree->getFlatTree() as $item) {
            $categories[] = [
                'id'       => $item['id'],
                'name'     => $item['name'],
                'selected' => $item['id'] === (int) $section->parent,
            ];
        }

        return $this->render->render('admin::forum/edit', [
            'item'        => $fields,
            'errors'      => $errors,
            'categories'  => $categories,
            'form_action' => self::URL . '/' . $section->id . '/edit',
            'back_url'    => self::URL . ($section->parent ? '?id=' . $section->parent : ''),
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
    private function fieldsFromRequest(ForumSection $section): array
    {
        return [
            'name'             => trim((string) $this->request->getPost('name', $section->name)),
            'description'      => trim((string) $this->request->getPost('description', $section->description)),
            'sort'             => (int) $this->request->getPost('sort', $section->sort ?? 100, FILTER_VALIDATE_INT),
            'section_type'     => (int) $this->request->getPost('section_type', (int) ($section->section_type ?? 0), FILTER_VALIDATE_INT),
            'parent'           => (int) $this->request->getPost('parent', (int) ($section->parent ?? 0), FILTER_VALIDATE_INT),
            'access'           => (int) $this->request->getPost('access', (int) ($section->access ?? 0), FILTER_VALIDATE_INT),
            'meta_description' => trim((string) $this->request->getPost('meta_description', $section->meta_description ?? '')),
            'meta_keywords'    => trim((string) $this->request->getPost('meta_keywords', $section->meta_keywords ?? '')),
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

    private function error(string $message): string
    {
        $title = __('Forum structure');
        $this->render->addData($this->menu($title));

        return $this->render->render('system::pages/result', [
            'title'    => $title,
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

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
