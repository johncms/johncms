<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemListItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionItemsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class CollectionItemsAdminController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionSectionRepositoryInterface $sectionRepository,
        private ContentCollectionFieldRepositoryInterface $fieldRepository,
        private ContentCollectionItemRepositoryInterface $itemRepository,
        private ListCollectionItemsUseCase $listItems,
        private SaveCollectionItemUseCase $saveItem,
        private DeleteCollectionItemUseCase $deleteItem,
        private Session $session,
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

        $sectionId = $this->querySection();

        $pagination = $this->paginationFactory->create($this->listItems->count($collection_id, $sectionId));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $items = $this->listItems->getPage($collection_id, $sectionId, $pagination->getPerPage(), $pagination->getOffset());

        $this->breadcrumbs($collection);
        $title = __('Items');
        $this->render->addData($this->menu($this->pageTitle($title, $pagination->getCurrentPage()), $collection->name));

        return $this->render->render('collections::admin/items/index', [
            'collection_name' => $collection->name,
            'items'           => $this->mapRows($collection_id, $items),
            'add_url'         => $this->baseUrl($collection_id) . '/new',
            'back_url'        => '/admin/collections',
            'pagination'      => $pagination->render(),
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    public function newForm(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        return $this->renderForm($collection, null, $this->defaultFields($this->querySection()), []);
    }

    public function editForm(int $collection_id, int $id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $item = $this->itemRepository->findWithValues($id);
        if ($item === null || $item->collection_id !== $collection_id) {
            return $this->wrongData($collection_id);
        }

        return $this->renderForm($collection, $id, $this->fieldsFromItem($item), $item->getValuesMap());
    }

    public function store(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        if (! $this->isCsrfValid()) {
            return $this->wrongData($collection_id);
        }

        $id = $this->request->bodyInt('id') ?: null;
        if ($id !== null && $this->findOwnedItem($collection_id, $id) === null) {
            return $this->wrongData($collection_id);
        }

        $fieldDefs = $this->fieldRepository->getByCollection($collection_id);
        $fields = $this->fieldsFromRequest($collection, $fieldDefs);
        $errors = $this->validate($collection, $fields);

        if ($errors !== []) {
            return $this->renderFormWithFields($collection, $id, $fields, $errors);
        }

        try {
            $this->saveItem->execute($id, $this->dtoFromFields($collection_id, $fields));
        } catch (CollectionItemCodeAlreadyExistsException) {
            return $this->renderFormWithFields($collection, $id, $fields, [__('An item with this code already exists')]);
        }

        // $id is non-null only for an existing, owned item (guarded above), so it reliably marks an update.
        $this->session->flash('success_message', $id !== null ? __('Changes saved') : __('Created successfully'));
        redirect($this->baseUrl($collection_id));
    }

    public function deleteConfirm(int $collection_id, int $id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $item = $this->findOwnedItem($collection_id, $id);
        if ($item === null) {
            return $this->wrongData($collection_id);
        }

        $this->breadcrumbs($collection);
        $title = __('Delete');
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $collection->name));

        return $this->render->render('collections::admin/delete_confirm', [
            'message'     => __('Are you sure you want to delete the item?'),
            'name'        => $item->name,
            'form_action' => $this->baseUrl($collection_id) . '/' . $id . '/delete',
            'back_url'    => $this->baseUrl($collection_id),
        ]);
    }

    public function delete(int $collection_id, int $id): string
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return $this->collectionNotFound();
        }

        if ($this->isCsrfValid() && $this->findOwnedItem($collection_id, $id) !== null) {
            $this->deleteItem->execute($id);
            $this->session->flash('success_message', __('Deleted successfully'));
        }

        redirect($this->baseUrl($collection_id));
    }

    private function findOwnedItem(int $collectionId, int $id): ?ContentCollectionItem
    {
        $item = $this->itemRepository->findById($id);

        return $item !== null && $item->collection_id === $collectionId ? $item : null;
    }

    /**
     * @param list<CollectionItemListItemDTO> $items
     * @return list<array<string, mixed>>
     */
    private function mapRows(int $collectionId, array $items): array
    {
        $base = $this->baseUrl($collectionId);

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'name'       => $item->name,
                'code'       => $item->code,
                'active'     => $item->active,
                'sort'       => $item->sort,
                'edit_url'   => $base . '/' . $item->id . '/edit',
                'delete_url' => $base . '/' . $item->id . '/delete',
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $valueMap field code => cast value(s)
     * @param list<string> $errors
     */
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $valueMap, array $errors = []): string
    {
        $this->breadcrumbs($collection);
        $title = $id !== null ? __('Edit item') : __('New item');
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $collection->name));

        $hasSections = $this->hasSections($collection);

        return $this->render->render('collections::admin/items/form', [
            'form_action'     => $this->baseUrl($collection->id),
            'back_url'        => $this->baseUrl($collection->id),
            'id'              => $id,
            'fields'          => $fields,
            'has_sections'    => $hasSections,
            'section_options' => $hasSections ? $this->sectionOptions($collection->id) : [],
            'field_defs'      => $this->buildFieldDefs($this->fieldRepository->getByCollection($collection->id), $valueMap),
            'errors'          => $errors,
        ]);
    }

    /**
     * Re-render the form on validation error, reusing the already submitted
     * custom field values so nothing is lost.
     *
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderFormWithFields(ContentCollection $collection, ?int $id, array $fields, array $errors): string
    {
        return $this->renderForm($collection, $id, $fields, $this->valueMapFromSubmitted($fields), $errors);
    }

    /**
     * @param Collection<int, \Johncms\Modules\Collections\Domain\Models\ContentCollectionField> $fields
     * @param array<string, mixed> $valueMap
     * @return list<array<string, mixed>>
     */
    private function buildFieldDefs(Collection $fields, array $valueMap): array
    {
        $defs = [];
        foreach ($fields as $field) {
            $current = $valueMap[$field->code] ?? null;

            if ($field->multiple) {
                $value = implode("\n", array_map(static fn ($v): string => (string) $v, is_array($current) ? $current : []));
            } elseif ($field->type === FieldType::Boolean) {
                $value = ! empty($current) ? '1' : '0';
            } else {
                $value = is_array($current) ? '' : (string) ($current ?? '');
            }

            $defs[] = [
                'input_name' => 'field_' . $field->code,
                'name'       => $field->name,
                'type'       => $field->type->value,
                'multiple'   => $field->multiple,
                'required'   => $field->required,
                'value'      => $value,
            ];
        }

        return $defs;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function valueMapFromSubmitted(array $fields): array
    {
        return $fields['values'] ?? [];
    }

    /**
     * @param Collection<int, \Johncms\Modules\Collections\Domain\Models\ContentCollectionField> $fieldDefs
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(ContentCollection $collection, Collection $fieldDefs): array
    {
        return [
            'section_id'   => $this->hasSections($collection) ? $this->postSection() : null,
            'code'         => trim($this->request->body('code', '')),
            'name'         => trim($this->request->body('name', '')),
            'active'       => $this->request->hasBody('active') ? 1 : 0,
            'active_from'  => trim($this->request->body('active_from', '')),
            'active_to'    => trim($this->request->body('active_to', '')),
            'sort'         => $this->request->bodyInt('sort', 100),
            'preview_text' => trim($this->request->body('preview_text', '')),
            'detail_text'  => trim($this->request->body('detail_text', '')),
            'values'       => $this->submittedValues($fieldDefs),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromItem(ContentCollectionItem $item): array
    {
        return [
            'section_id'   => $item->section_id,
            'code'         => $item->code,
            'name'         => $item->name,
            'active'       => $item->active ? 1 : 0,
            'active_from'  => $item->active_from?->format('Y-m-d H:i:s') ?? '',
            'active_to'    => $item->active_to?->format('Y-m-d H:i:s') ?? '',
            'sort'         => $item->sort,
            'preview_text' => (string) $item->preview_text,
            'detail_text'  => (string) $item->detail_text,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFields(?int $sectionId): array
    {
        return [
            'section_id'   => $sectionId,
            'code'         => '',
            'name'         => '',
            'active'       => 1,
            'active_from'  => '',
            'active_to'    => '',
            'sort'         => 100,
            'preview_text' => '',
            'detail_text'  => '',
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function dtoFromFields(int $collectionId, array $fields): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO(
            collectionId: $collectionId,
            sectionId: $fields['section_id'] !== null ? (int) $fields['section_id'] : null,
            code: (string) $fields['code'],
            name: (string) $fields['name'],
            active: (bool) $fields['active'],
            activeFrom: $fields['active_from'] !== '' ? (string) $fields['active_from'] : null,
            activeTo: $fields['active_to'] !== '' ? (string) $fields['active_to'] : null,
            sort: (int) $fields['sort'],
            previewText: $fields['preview_text'] !== '' ? (string) $fields['preview_text'] : null,
            detailText: $fields['detail_text'] !== '' ? (string) $fields['detail_text'] : null,
            values: $fields['values'] ?? [],
        );
    }

    /**
     * @param array<string, mixed> $fields
     * @return list<string>
     */
    private function validate(ContentCollection $collection, array $fields): array
    {
        $errors = [];
        // Code is optional: when left empty it is generated from the name as a unique slug.
        if ($fields['name'] === '') {
            $errors[] = __('The required fields are not filled');
        }

        if ($fields['code'] !== '' && ! preg_match('/^[a-z0-9_-]+$/', (string) $fields['code'])) {
            $errors[] = __('The code may contain only lowercase latin letters, digits, hyphen and underscore');
        }

        if (
            $this->hasSections($collection) && $fields['section_id'] !== null
            && ! $this->sectionBelongs($collection->id, (int) $fields['section_id'])
        ) {
            $errors[] = __('Wrong data');
        }

        return $errors;
    }

    /**
     * @param Collection<int, \Johncms\Modules\Collections\Domain\Models\ContentCollectionField> $fieldDefs
     * @return array<string, string|list<string>>
     */
    private function submittedValues(Collection $fieldDefs): array
    {
        $values = [];
        foreach ($fieldDefs as $field) {
            $raw = $this->request->body('field_' . $field->code, '');
            if ($field->multiple) {
                $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                $values[$field->code] = array_values(array_filter(
                    array_map('trim', $lines),
                    static fn (string $value): bool => $value !== ''
                ));
            } else {
                $values[$field->code] = $raw;
            }
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function sectionOptions(int $collectionId): array
    {
        $options = [];
        foreach ($this->sectionRepository->getAllByCollection($collectionId) as $section) {
            $options[$section->id] = $section->name;
        }

        return $options;
    }

    private function sectionBelongs(int $collectionId, int $sectionId): bool
    {
        $section = $this->sectionRepository->findById($sectionId);

        return $section !== null && $section->collection_id === $collectionId;
    }

    private function hasSections(ContentCollection $collection): bool
    {
        return ! empty($collection->settings['has_sections']);
    }

    private function querySection(): ?int
    {
        return ($this->request->queryInt('section')) ?: null;
    }

    private function postSection(): ?int
    {
        return ($this->request->bodyInt('section_id')) ?: null;
    }

    private function baseUrl(int $collectionId): string
    {
        return '/admin/collections/' . $collectionId . '/items';
    }

    private function pageTitle(string $title, int $page): string
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
            'page_title'  => __('Items'),
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

    private function wrongData(int $collectionId): string
    {
        $this->render->addData($this->menu(__('Items'), __('Items')));

        return $this->render->render('system::pages/result', [
            'title'    => __('Items'),
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => $this->baseUrl($collectionId),
        ]);
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
