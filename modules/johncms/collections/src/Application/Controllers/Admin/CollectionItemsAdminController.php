<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionItemsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\Modules\Collections\Application\Services\ItemListControlsMapper;
use Johncms\Modules\Collections\Application\Services\ItemListFilterMapper;
use Johncms\Modules\Collections\Application\Services\ItemListRowMapper;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;

final readonly class CollectionItemsAdminController
{
    public function __construct(
        private NavChain $navChain,
        private CollectionWorkspaceMapper $workspace,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionFieldRepositoryInterface $fieldRepository,
        private ContentCollectionItemRepositoryInterface $itemRepository,
        private ListCollectionItemsUseCase $listItems,
        private SaveCollectionItemUseCase $saveItem,
        private DeleteCollectionItemUseCase $deleteItem,
        private Session $session,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private ItemListFilterMapper $filterMapper,
        private ItemListControlsMapper $controlsMapper,
        private ItemListRowMapper $rowMapper,
        private \Johncms\Modules\Collections\Application\Services\CollectionItemFiles $files,
    ) {
    }

    public function index(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findWithCounts($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $filter = $this->filterMapper->fromQuery($request->query->all());
        $tree = $this->listItems->sectionTree($collection_id);
        $pagination = $this->paginationFactory->create($this->listItems->count($collection_id, $filter, $tree));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $listUrl = $pagination->getUrl($pagination->getCurrentPage());
        $fields = $this->listItems->listedFields($collection_id);
        $hasSections = $this->hasSections($collection);
        $items = $this->listItems->getPage($collection_id, $filter, $tree, $pagination->getPerPage(), $pagination->getOffset());

        $data = [
            'controls'     => $this->controlsMapper->map($this->baseUrl($collection_id), $filter, $tree, $hasSections),
            'columns'      => array_map(static fn ($field): string => $field->name, $fields),
            'has_sections' => $hasSections,
            'rows'         => $this->rowMapper->rows($items, $fields, $tree, $collection, $listUrl),
            'search'       => $filter->search,
            'total'        => $pagination->getTotal(),
            'pagination'   => $pagination->render(),
            'add_url'      => $this->baseUrl($collection_id) . '/new' . ($filter->section ? '?section=' . $filter->section : ''),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@collections/admin/items-live.twig', $data);
        }

        $this->breadcrumbs($collection);

        return new ViewResponse('@collections/admin/items.twig', [
            'page_title' => $collection->name,
            'collection' => $this->workspace->head($collection, CollectionWorkspaceMapper::ITEMS),
        ] + $this->menu($this->pageTitle(__('Items'), $pagination->getCurrentPage()), $collection->name, $collection) + $data + [
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    public function newForm(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $defaults = [];
        foreach ($this->fieldRepository->getByCollection($collection_id) as $field) {
            if (isset($field->settings['default'])) {
                $defaults[$field->code] = $field->settings['default'];
            }
        }
        return $this->renderForm($collection, null, $this->defaultFields($this->querySection($request)), $defaults);
    }

    public function editForm(int $collection_id, int $id): ViewResponse
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

    public function store(Request $request, CurrentUser $user, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $id = $request->bodyInt('id') ?: null;
        if ($id !== null && $this->findOwnedItem($collection_id, $id) === null) {
            return $this->wrongData($collection_id);
        }

        $fieldDefs = $this->fieldRepository->getByCollection($collection_id);
        $fields = $this->fieldsFromRequest($request, $collection, $fieldDefs);
        try {
            $this->saveItem->execute($id, $this->dtoFromFields($collection_id, $fields), $user->id());
        } catch (CollectionItemFormException $exception) {
            return $this->renderFormWithFields($collection, $id, $fields, $exception->errors);
        } catch (CollectionWriteConflictException) {
            return $this->renderFormWithFields($collection, $id, $fields, ['_form' => [d__('collections', 'The data changed during saving. Please try again.')]]);
        } catch (CollectionItemCodeAlreadyExistsException) {
            return $this->renderFormWithFields($collection, $id, $fields, ['code' => [__('An item with this code already exists')]]);
        }

        // $id is non-null only for an existing, owned item (guarded above), so it reliably marks an update.
        $this->session->flash('success_message', $id !== null ? __('Changes saved') : __('Created successfully'));
        redirect($this->baseUrl($collection_id));
    }

    public function deleteConfirm(Request $request, int $collection_id, int $id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $item = $this->findOwnedItem($collection_id, $id);
        if ($item === null) {
            return $this->wrongData($collection_id);
        }

        $back = $this->returnUrl($collection_id, $request->queryParam('back'));
        $dialog = [
            'title'       => __('Delete item'),
            'message'     => __('Are you sure you want to delete the item?'),
            'name'        => $item->name,
            'form_action' => $this->baseUrl($collection_id) . '/' . $id . '/delete',
            'back_url'    => $back,
            'hidden'      => ['back' => $back],
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@collections/admin/delete-dialog.twig', $dialog);
        }

        $this->breadcrumbs($collection);
        $this->navChain->add($dialog['title']);

        return new ViewResponse('@collections/admin/delete-confirm.twig', $this->menu($dialog['title'], $collection->name, $collection) + [
            'dialog' => $dialog,
        ]);
    }

    public function delete(Request $request, int $collection_id, int $id): ViewResponse
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return $this->collectionNotFound();
        }

        if ($this->findOwnedItem($collection_id, $id) !== null) {
            try {
                $this->deleteItem->execute($id, $collection_id);
            } catch (CollectionItemFormException) {
                return $this->wrongData($collection_id);
            } catch (CollectionWriteConflictException) {
                return $this->wrongData($collection_id, d__('collections', 'The contents changed during deletion. Check the counts and try again.'));
            }
            $this->session->flash('success_message', __('Deleted successfully'));
        }

        redirect($this->returnUrl($collection_id, $request->body('back')));
    }

    /**
     * Where an action taken on the list returns to: the page of the list it was taken on, or the
     * list itself. An address that is not one of the list is not followed.
     */
    private function returnUrl(int $collectionId, string $back): string
    {
        $list = $this->baseUrl($collectionId);

        return $back === $list || str_starts_with($back, $list . '?') ? $back : $list;
    }

    private function findOwnedItem(int $collectionId, int $id): ?ContentCollectionItem
    {
        $item = $this->itemRepository->findById($id);

        return $item !== null && $item->collection_id === $collectionId ? $item : null;
    }

    /**
     * @param array<string, mixed> $valueMap field code => cast value(s)
     * @param array<string, list<string>> $errors
     */
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $valueMap, array $errors = []): ViewResponse
    {
        $this->breadcrumbs($collection);
        $fields['upload_context'] = $fields['upload_context'] ?? $this->files->createContext($collection->id, $id);
        $title = $id !== null ? __('Edit item') : __('New item');
        $this->navChain->add($title);

        $hasSections = $this->hasSections($collection);

        return new ViewResponse('@collections/admin/item-form.twig', $this->menu($title, $collection->name, $collection) + [
            'upload_url'      => '/admin/collections/' . $collection->id . '/uploads?context=' . rawurlencode($fields['upload_context']),
            'form_action'     => $this->baseUrl($collection->id),
            'back_url'        => $this->baseUrl($collection->id),
            'id'              => $id,
            'fields'          => $fields,
            'has_sections'    => $hasSections,
            'section_options' => $hasSections ? $this->sectionOptions($collection->id) : [],
            'field_defs'      => $this->buildFieldDefs($this->fieldRepository->getByCollection($collection->id), $valueMap, $errors),
            'errors'          => array_merge([], ...array_values($errors)),
            'field_errors'    => $errors,
        ]);
    }

    /**
     * Re-render the form on validation error, reusing the already submitted
     * custom field values so nothing is lost.
     *
     * @param array<string, mixed> $fields
     * @param array<string, list<string>> $errors
     */
    private function renderFormWithFields(ContentCollection $collection, ?int $id, array $fields, array $errors): ViewResponse
    {
        return $this->renderForm($collection, $id, $fields, $this->valueMapFromSubmitted($fields), $errors);
    }

    /**
     * @param Collection<int, \Johncms\Modules\Collections\Domain\Models\ContentCollectionField> $fields
     * @param array<string, mixed> $valueMap
     * @return list<array<string, mixed>>
     */
    private function buildFieldDefs(Collection $fields, array $valueMap, array $errors): array
    {
        $defs = [];
        foreach ($fields as $field) {
            $current = $valueMap[$field->code] ?? null;
            if ($field->role === 'tags' && is_array($current)) {
                $current = array_map(static fn (string $value): string => \Johncms\Modules\Collections\Domain\Services\CollectionTextNormalizer::normalize($value), $current);
            }

            if ($field->multiple) {
                $value = implode("\n", array_map(static fn ($v): string => (string) $v, is_array($current) ? $current : []));
            } elseif ($field->type === FieldType::Boolean) {
                $value = ! empty($current) ? '1' : '0';
            } else {
                $value = is_array($current) ? '' : (string) ($current ?? '');
                // The formats of <input type="date"> and <input type="datetime-local">.
                if ($value !== '' && $field->type === FieldType::Date) {
                    $value = substr($value, 0, 10);
                } elseif ($value !== '' && $field->type === FieldType::Datetime) {
                    $value = str_replace(' ', 'T', substr($value, 0, 16));
                }
            }

            $defs[] = [
                'tags_url' => $field->role === 'tags' ? '/admin/collections/' . $field->collection_id . '/tags/' . $field->code : '',
                'role' => $field->role,
                'entries'    => $field->multiple ? [...array_map(fn ($entry): string => $this->inputValue($field->type, $entry), is_array($current) ? $current : []), ''] : [],
                'input_name' => 'field_' . $field->code,
                'name'       => $field->name,
                'hint' => $field->settings['hint'] ?? '',
                'min_length' => $field->settings['min_length'] ?? null, 'max_length' => $field->settings['max_length'] ?? null,
                'min_value' => $field->settings['min_value'] ?? null, 'max_value' => $field->settings['max_value'] ?? null,
                'type'       => $field->type->value,
                'multiple'   => $field->multiple,
                'required'   => $field->required,
                'value'      => $value,
                'errors'     => $errors['values.' . $field->code] ?? [],
            ];
        }

        return $defs;
    }

    private function inputValue(FieldType $type, mixed $value): string
    {
        if ($type === FieldType::Boolean) {
            return $value === false || $value === '0' ? '0' : ($value === true || $value === '1' ? '1' : '');
        }
        $value = (string) $value;
        return match ($type) {
            FieldType::Date => substr($value, 0, 10),
            FieldType::Datetime => str_replace(' ', 'T', substr($value, 0, 16)),
            default => $value,
        };
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
    private function fieldsFromRequest(Request $request, ContentCollection $collection, Collection $fieldDefs): array
    {
        return [
            'upload_context' => $request->body('upload_context') ?: null,
            'section_id'   => $this->hasSections($collection) ? $this->postSection($request) : null,
            'code'         => trim($request->body('code', '')),
            'name'         => trim($request->body('name', '')),
            'active'       => $request->hasBody('active') ? 1 : 0,
            'active_from'  => trim($request->body('active_from', '')),
            'active_to'    => trim($request->body('active_to', '')),
            'sort'         => $request->body('sort', '100'),
            'preview_text' => trim($request->body('preview_text', '')),
            'detail_text'  => trim($request->body('detail_text', '')),
            'values'       => $this->submittedValues($request, $fieldDefs),
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
            // The format of an <input type="datetime-local">.
            'active_from'  => $item->active_from?->format('Y-m-d\TH:i') ?? '',
            'active_to'    => $item->active_to?->format('Y-m-d\TH:i') ?? '',
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
            activeFrom: $fields['active_from'],
            activeTo: $fields['active_to'],
            sort: $fields['sort'],
            previewText: $fields['preview_text'] !== '' ? (string) $fields['preview_text'] : null,
            detailText: $fields['detail_text'] !== '' ? (string) $fields['detail_text'] : null,
            values: $fields['values'] ?? [],
            uploadContext: $fields['upload_context'] ?? null,
        );
    }

    /**
     * @param Collection<int, \Johncms\Modules\Collections\Domain\Models\ContentCollectionField> $fieldDefs
     * @return array<string, string|list<string>>
     */
    private function submittedValues(Request $request, Collection $fieldDefs): array
    {
        $values = [];
        foreach ($fieldDefs as $field) {
            if ($field->multiple && is_array($request->request->all()['field_' . $field->code] ?? null)) {
                $values[$field->code] = array_values(array_filter($request->bodyList('field_' . $field->code), static fn ($value): bool => is_string($value) && trim($value) !== ''));
                continue;
            }
            $raw = $request->body('field_' . $field->code, '');
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
     * The sections an item may stand in, in the order of the tree, each named by its path.
     *
     * @return array<int, string>
     */
    private function sectionOptions(int $collectionId): array
    {
        $tree = $this->listItems->sectionTree($collectionId);
        $options = [];
        foreach ($tree->ordered() as $id) {
            $options[$id] = implode(' › ', $tree->pathOf($id));
        }

        return $options;
    }

    private function hasSections(ContentCollection $collection): bool
    {
        return ! empty($collection->settings['has_sections']);
    }

    private function querySection(Request $request): ?int
    {
        return ($request->queryInt('section')) ?: null;
    }

    private function postSection(Request $request): ?int
    {
        return filter_var($request->body('section_id', '0'), FILTER_VALIDATE_INT) ?: ($request->body('section_id', '0') === '0' ? null : -1);
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
        if ($this->workspace->canManageSchema()) {
            $this->navChain->add(__('Collections'), '/admin/collections');
        }
        $this->navChain->add($collection->name, $this->baseUrl($collection->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title, string $collectionName, ?ContentCollection $collection = null): array
    {
        return [
            'title'       => $title . ' — ' . $collectionName,
            'page_title'  => $title,
            'module_menu' => $collection !== null ? $this->workspace->menu($collection) : ['collections' => true],
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

    private function wrongData(int $collectionId, ?string $message = null): ViewResponse
    {
        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Items'), __('Items')) + [
            'title'    => __('Items'),
            'type'     => 'alert-danger',
            'message'  => $message ?? __('Wrong data'),
            'back_url' => $this->baseUrl($collectionId),
        ]);
    }
}
