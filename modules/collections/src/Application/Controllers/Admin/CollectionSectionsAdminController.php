<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionListItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionSectionUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionSectionsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class CollectionSectionsAdminController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionSectionRepositoryInterface $sectionRepository,
        private ListCollectionSectionsUseCase $listSections,
        private SaveCollectionSectionUseCase $saveSection,
        private DeleteCollectionSectionUseCase $deleteSection,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('collections');
    }

    public function index(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $parent = $this->queryParent();
        $parentSection = $this->resolveParent($collection_id, $parent);
        if ($parent !== null && $parentSection === null) {
            redirect($this->baseUrl($collection_id));
        }

        $pagination = $this->paginationFactory->create($this->listSections->count($collection_id, $parent));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $sections = $this->listSections->getPage($collection_id, $parent, $pagination->getPerPage(), $pagination->getOffset());

        $this->breadcrumbs($collection);
        $title = __('Sections');
        $this->render->addData($this->menu($this->pageMeta($title, $pagination->getCurrentPage()), $collection->name));

        return $this->render->render('collections::admin/sections/index', [
            'collection_name' => $collection->name,
            'root_url'        => $this->baseUrl($collection_id),
            'path'            => $this->pathItems($collection_id, $parentSection),
            'items'           => $this->mapRows($collection_id, $sections),
            'add_url'         => $this->urlWithParent($this->baseUrl($collection_id) . '/new', $parent),
            'back_url'        => $this->parentUpUrl($collection_id, $parentSection),
            'pagination'      => $pagination->render(),
            'success_message' => $this->pullFlash(),
        ]);
    }

    public function newForm(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $parent = $this->queryParent();
        if ($parent !== null && $this->resolveParent($collection_id, $parent) === null) {
            redirect($this->baseUrl($collection_id));
        }

        return $this->renderForm($collection, null, $this->defaultFields($parent));
    }

    public function editForm(int $collection_id, int $id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $section = $this->findOwnedSection($collection_id, $id);
        if ($section === null) {
            return $this->wrongData($collection_id, null);
        }

        return $this->renderForm($collection, $id, $this->fieldsFromSection($section));
    }

    public function store(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        if (! $this->isCsrfValid()) {
            return $this->wrongData($collection_id, null);
        }

        $id = $this->request->bodyInt('id') ?: null;

        // The parent is fixed on edit (taken from the section) and comes from the
        // form context on create; a create parent must belong to the collection.
        if ($id !== null) {
            $section = $this->findOwnedSection($collection_id, $id);
            if ($section === null) {
                return $this->wrongData($collection_id, null);
            }
            $parent = $section->parent;
        } else {
            $parent = $this->postParent();
            if ($parent !== null && $this->resolveParent($collection_id, $parent) === null) {
                return $this->wrongData($collection_id, null);
            }
        }

        $fields = $this->fieldsFromRequest($parent);
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($collection, $id, $fields, $errors);
        }

        try {
            $isUpdate = $this->saveSection->execute($id, $this->dtoFromFields($collection_id, $fields));
        } catch (CollectionSectionCodeAlreadyExistsException) {
            return $this->renderForm($collection, $id, $fields, [__('A section with this code already exists')]);
        }

        $_SESSION['success_message'] = $isUpdate ? __('Changes saved') : __('Created successfully');
        redirect($this->urlWithParent($this->baseUrl($collection_id), $parent));
    }

    public function deleteConfirm(int $collection_id, int $id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $section = $this->findOwnedSection($collection_id, $id);
        if ($section === null) {
            return $this->wrongData($collection_id, null);
        }

        $this->breadcrumbs($collection);
        $title = __('Delete');
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $collection->name));

        return $this->render->render('collections::admin/delete_confirm', [
            'message'     => __('Are you sure you want to delete the section?')
                . ' ' . __('Nested sections will be deleted too.'),
            'name'        => $section->name,
            'form_action' => $this->baseUrl($collection_id) . '/' . $id . '/delete',
            'back_url'    => $this->urlWithParent($this->baseUrl($collection_id), $section->parent),
        ]);
    }

    public function delete(int $collection_id, int $id): string
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return $this->collectionNotFound();
        }

        $section = $this->findOwnedSection($collection_id, $id);
        $parent = $section?->parent;

        if ($this->isCsrfValid() && $section !== null) {
            $this->deleteSection->execute($id);
            $_SESSION['success_message'] = __('Deleted successfully');
        }

        redirect($this->urlWithParent($this->baseUrl($collection_id), $parent));
    }

    private function findOwnedSection(int $collectionId, int $id): ?ContentCollectionSection
    {
        $section = $this->sectionRepository->findById($id);

        return $section !== null && $section->collection_id === $collectionId ? $section : null;
    }

    private function resolveParent(int $collectionId, ?int $parent): ?ContentCollectionSection
    {
        return $parent !== null ? $this->findOwnedSection($collectionId, $parent) : null;
    }

    /**
     * @param list<CollectionSectionListItemDTO> $items
     * @return list<array<string, mixed>>
     */
    private function mapRows(int $collectionId, array $items): array
    {
        $base = $this->baseUrl($collectionId);

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'name'        => $item->name,
                'code'        => $item->code,
                'active'      => $item->active,
                'sort'        => $item->sort,
                'child_count' => $item->childCount,
                'browse_url'  => $base . '?parent=' . $item->id,
                'edit_url'    => $base . '/' . $item->id . '/edit',
                'delete_url'  => $base . '/' . $item->id . '/delete',
            ];
        }

        return $rows;
    }

    /**
     * Breadcrumb path from the root down to the current parent.
     *
     * @return list<array{name: string, url: string}>
     */
    private function pathItems(int $collectionId, ?ContentCollectionSection $parentSection): array
    {
        if ($parentSection === null) {
            return [];
        }

        $base = $this->baseUrl($collectionId);
        $items = [];
        foreach ($this->sectionRepository->getPathTo($parentSection->id) as $section) {
            $items[] = ['name' => $section->name, 'url' => $base . '?parent=' . $section->id];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $errors = []): string
    {
        $this->breadcrumbs($collection);
        $title = $id !== null ? __('Edit section') : __('New section');
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $collection->name));

        $parent = $fields['parent'] !== null ? (int) $fields['parent'] : null;

        return $this->render->render('collections::admin/sections/form', [
            'form_action' => $this->baseUrl($collection->id),
            'back_url'    => $this->urlWithParent($this->baseUrl($collection->id), $parent),
            'id'          => $id,
            'fields'      => $fields,
            'errors'      => $errors,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(?int $parent): array
    {
        return [
            'parent'      => $parent,
            'code'        => trim($this->request->body('code', '')),
            'name'        => trim($this->request->body('name', '')),
            'description' => trim($this->request->body('description', '')),
            'active'      => $this->request->hasBody('active') ? 1 : 0,
            'sort'        => $this->request->bodyInt('sort', 100),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromSection(ContentCollectionSection $section): array
    {
        return [
            'parent'      => $section->parent,
            'code'        => $section->code,
            'name'        => $section->name,
            'description' => (string) $section->description,
            'active'      => $section->active ? 1 : 0,
            'sort'        => $section->sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFields(?int $parent): array
    {
        return [
            'parent'      => $parent,
            'code'        => '',
            'name'        => '',
            'description' => '',
            'active'      => 1,
            'sort'        => 100,
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function dtoFromFields(int $collectionId, array $fields): CollectionSectionFormDTO
    {
        return new CollectionSectionFormDTO(
            collectionId: $collectionId,
            parent: $fields['parent'] !== null ? (int) $fields['parent'] : null,
            code: (string) $fields['code'],
            name: (string) $fields['name'],
            description: $fields['description'] !== '' ? (string) $fields['description'] : null,
            active: (bool) $fields['active'],
            sort: (int) $fields['sort'],
        );
    }

    /**
     * @param array<string, mixed> $fields
     * @return list<string>
     */
    private function validate(array $fields): array
    {
        $errors = [];
        if ($fields['code'] === '' || $fields['name'] === '') {
            $errors[] = __('The required fields are not filled');
        }

        if ($fields['code'] !== '' && ! preg_match('/^[a-z0-9_-]+$/', (string) $fields['code'])) {
            $errors[] = __('The code may contain only lowercase latin letters, digits, hyphen and underscore');
        }

        return $errors;
    }

    private function queryParent(): ?int
    {
        return ($this->request->queryInt('parent')) ?: null;
    }

    private function postParent(): ?int
    {
        return ($this->request->bodyInt('parent')) ?: null;
    }

    private function baseUrl(int $collectionId): string
    {
        return '/admin/collections/' . $collectionId . '/sections';
    }

    private function urlWithParent(string $url, ?int $parent): string
    {
        return $parent !== null ? $url . '?parent=' . $parent : $url;
    }

    private function parentUpUrl(int $collectionId, ?ContentCollectionSection $parentSection): string
    {
        if ($parentSection === null) {
            return '/admin/collections';
        }

        return $this->urlWithParent($this->baseUrl($collectionId), $parentSection->parent);
    }

    private function pageMeta(string $title, int $page): string
    {
        return (new PageMeta($title, $page))->title;
    }

    private function breadcrumbs(ContentCollection $collection): void
    {
        $this->navChain->add(__('Collections'), '/admin/collections');
        $this->navChain->add($collection->name, $this->baseUrl($collection->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title, string $collectionName): array
    {
        return [
            'title'       => $title . ' — ' . $collectionName,
            'page_title'  => __('Sections'),
            'module_menu' => ['collections' => true],
        ];
    }

    private function collectionNotFound(): string
    {
        $this->render->addData($this->menu(__('Collections'), __('Collections')));

        return $this->render->render('system::pages/result', [
            'title'    => __('Collections'),
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => '/admin/collections',
        ]);
    }

    private function wrongData(int $collectionId, ?int $parent): string
    {
        $this->render->addData($this->menu(__('Sections'), __('Sections')));

        return $this->render->render('system::pages/result', [
            'title'    => __('Sections'),
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => $this->urlWithParent($this->baseUrl($collectionId), $parent),
        ]);
    }

    private function pullFlash(): string
    {
        if (empty($_SESSION['success_message'])) {
            return '';
        }

        $message = (string) $_SESSION['success_message'];
        unset($_SESSION['success_message']);

        return $message;
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
