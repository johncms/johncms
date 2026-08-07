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
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

final readonly class CollectionSectionsAdminController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private NavChain $navChain,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionSectionRepositoryInterface $sectionRepository,
        private ListCollectionSectionsUseCase $listSections,
        private SaveCollectionSectionUseCase $saveSection,
        private DeleteCollectionSectionUseCase $deleteSection,
        private Session $session,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('collections');
    }

    public function index(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $parent = $this->queryParent($request);
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

        return new ViewResponse('@collections/admin/sections.twig', $this->menu($this->pageMeta($title, $pagination->getCurrentPage()), $collection->name) + [
            'collection_name' => $collection->name,
            'root_url'        => $this->baseUrl($collection_id),
            'path'            => $this->pathItems($collection_id, $parentSection),
            'items'           => $this->mapRows($collection_id, $sections),
            'add_url'         => $this->urlWithParent($this->baseUrl($collection_id) . '/new', $parent),
            'back_url'        => $this->parentUpUrl($collection_id, $parentSection),
            'pagination'      => $pagination->render(),
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    public function newForm(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $parent = $this->queryParent($request);
        if ($parent !== null && $this->resolveParent($collection_id, $parent) === null) {
            redirect($this->baseUrl($collection_id));
        }

        return $this->renderForm($collection, null, $this->defaultFields($parent));
    }

    public function editForm(int $collection_id, int $id): ViewResponse
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

    public function store(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        if (! $this->isCsrfValid($request)) {
            return $this->wrongData($collection_id, null);
        }

        $id = $request->bodyInt('id') ?: null;

        // The parent is fixed on edit (taken from the section) and comes from the
        // form context on create; a create parent must belong to the collection.
        if ($id !== null) {
            $section = $this->findOwnedSection($collection_id, $id);
            if ($section === null) {
                return $this->wrongData($collection_id, null);
            }
            $parent = $section->parent;
        } else {
            $parent = $this->postParent($request);
            if ($parent !== null && $this->resolveParent($collection_id, $parent) === null) {
                return $this->wrongData($collection_id, null);
            }
        }

        $fields = $this->fieldsFromRequest($request, $parent);
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($collection, $id, $fields, $errors);
        }

        try {
            $isUpdate = $this->saveSection->execute($id, $this->dtoFromFields($collection_id, $fields));
        } catch (CollectionSectionCodeAlreadyExistsException) {
            return $this->renderForm($collection, $id, $fields, [__('A section with this code already exists')]);
        }

        $this->session->flash('success_message', $isUpdate ? __('Changes saved') : __('Created successfully'));
        redirect($this->urlWithParent($this->baseUrl($collection_id), $parent));
    }

    public function deleteConfirm(int $collection_id, int $id): ViewResponse
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

        return new ViewResponse('@collections/admin/delete-confirm.twig', $this->menu($title, $collection->name) + [
            'message'     => __('Are you sure you want to delete the section?')
                . ' ' . __('Nested sections will be deleted too.'),
            'name'        => $section->name,
            'form_action' => $this->baseUrl($collection_id) . '/' . $id . '/delete',
            'back_url'    => $this->urlWithParent($this->baseUrl($collection_id), $section->parent),
        ]);
    }

    public function delete(Request $request, int $collection_id, int $id): ViewResponse
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return $this->collectionNotFound();
        }

        $section = $this->findOwnedSection($collection_id, $id);
        $parent = $section?->parent;

        if ($this->isCsrfValid($request) && $section !== null) {
            $this->deleteSection->execute($id);
            $this->session->flash('success_message', __('Deleted successfully'));
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
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $errors = []): ViewResponse
    {
        $this->breadcrumbs($collection);
        $title = $id !== null ? __('Edit section') : __('New section');
        $this->navChain->add($title);

        $parent = $fields['parent'] !== null ? (int) $fields['parent'] : null;

        return new ViewResponse('@collections/admin/section-form.twig', $this->menu($title, $collection->name) + [
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
    private function fieldsFromRequest(Request $request, ?int $parent): array
    {
        return [
            'parent'      => $parent,
            'code'        => trim($request->body('code', '')),
            'name'        => trim($request->body('name', '')),
            'description' => trim($request->body('description', '')),
            'active'      => $request->hasBody('active') ? 1 : 0,
            'sort'        => $request->bodyInt('sort', 100),
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

    private function queryParent(Request $request): ?int
    {
        return ($request->queryInt('parent')) ?: null;
    }

    private function postParent(Request $request): ?int
    {
        return ($request->bodyInt('parent')) ?: null;
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

    private function collectionNotFound(): ViewResponse
    {

        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Collections'), __('Collections')) + [
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => '/admin/collections',
        ]);
    }

    private function wrongData(int $collectionId, ?int $parent): ViewResponse
    {

        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Sections'), __('Sections')) + [
            'title'    => __('Sections'),
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => $this->urlWithParent($this->baseUrl($collectionId), $parent),
        ]);
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
