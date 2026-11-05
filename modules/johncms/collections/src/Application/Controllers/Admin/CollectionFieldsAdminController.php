<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldListItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionFieldCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionFieldUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionFieldsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionFieldUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;

final readonly class CollectionFieldsAdminController
{
    public function __construct(
        private NavChain $navChain,
        private CollectionWorkspaceMapper $workspace,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionFieldRepositoryInterface $fieldRepository,
        private ListCollectionFieldsUseCase $listFields,
        private SaveCollectionFieldUseCase $saveField,
        private DeleteCollectionFieldUseCase $deleteField,
        private Session $session,
        private \Johncms\Modules\Collections\Application\Services\CollectionRepeatedValues $repeated = new \Johncms\Modules\Collections\Application\Services\CollectionRepeatedValues(),
    ) {
    }

    public function index(int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findWithCounts($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $this->breadcrumbs($collection);
        $title = __('Fields');

        return new ViewResponse('@collections/admin/fields.twig', [
            'page_title' => $collection->name,
            'collection' => $this->workspace->head($collection, CollectionWorkspaceMapper::FIELDS),
        ] + $this->menu($title, $collection->name) + [
            'items'           => $this->mapRows($collection_id, $this->listFields->getByCollection($collection_id)),
            'add_url'         => $this->baseUrl($collection_id) . '/new',
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    public function newForm(int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        return $this->renderForm($collection, null, $this->defaultFields());
    }

    public function editForm(int $collection_id, int $id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $field = $this->findOwnedField($collection_id, $id);
        if ($field === null) {
            return $this->wrongData($collection_id);
        }

        return $this->renderForm($collection, $id, $this->fieldsFromField($field));
    }

    public function store(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        if ($request->hasBody('collection_form_started') && $request->body('collection_form_complete') !== '1') {
            return $this->wrongData($collection_id, d__('collections', 'The form was truncated. Nothing was saved. Reduce the number of values or ask the administrator to raise the form input limit.'));
        }

        $id = $request->bodyInt('id') ?: null;
        if ($id !== null && $this->findOwnedField($collection_id, $id) === null) {
            return $this->wrongData($collection_id);
        }

        $fields = $this->fieldsFromRequest($request);
        if ($request->hasBody('repeat_action')) {
            try {
                [$key, $operation, $index] = $this->repeated->action($request->body('repeat_action'));
                if (! in_array($key, ['default', 'options'], true)) {
                    throw new \InvalidArgumentException(d__('collections', 'Choose an existing value and a valid action.'));
                }
                if ($key === 'options') {
                    $keys = array_map(strval(...), array_keys($fields['option_rows']));
                    $ordered = $this->repeated->apply($keys, $operation, $index, 500);
                    $fields['option_rows'] = array_map(static fn (string $key): array => $key === '' ? ['code' => '', 'label' => '', 'active' => true] : $fields['option_rows'][(int) $key], $ordered);
                } else {
                    $fields['default_values'] = $this->repeated->apply($fields['default_values'], $operation, $index);
                }
                $fields['repeat_message'] = d__('collections', 'Values changed. Save the form to apply them.');
                return $this->renderForm($collection, $id, $fields);
            } catch (\InvalidArgumentException $exception) {
                return $this->renderForm($collection, $id, $fields, [$exception->getMessage()]);
            }
        }
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($collection, $id, $fields, $errors);
        }

        try {
            $this->saveField->execute($id, $this->dtoFromFields($collection_id, $fields));
        } catch (CollectionSchemaException $exception) {
            return $this->renderForm($collection, $id, $fields, [$exception->getMessage()]);
        } catch (CollectionFieldCodeAlreadyExistsException) {
            return $this->renderForm($collection, $id, $fields, [__('A field with this code already exists')]);
        }

        $this->session->flash('success_message', $id !== null ? __('Changes saved') : __('Created successfully'));
        redirect($this->baseUrl($collection_id));
    }

    public function deleteConfirm(Request $request, int $collection_id, int $id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $field = $this->findOwnedField($collection_id, $id);
        if ($field === null) {
            return $this->wrongData($collection_id);
        }

        if ($field->owner_module !== null) {
            return $this->wrongData($collection_id, d__('collections', 'This field is managed by its module. Change the declaration or use its trusted update hook.'));
        }
        $dialog = [
            'title'       => __('Delete field'),
            'message'     => __('Are you sure you want to delete the field?'),
            'name'        => $field->name,
            'form_action' => $this->baseUrl($collection_id) . '/' . $id . '/delete',
            'back_url'    => $this->baseUrl($collection_id),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@collections/admin/delete-dialog.twig', $dialog);
        }

        $this->breadcrumbs($collection);
        $this->navChain->add($dialog['title']);

        return new ViewResponse('@collections/admin/delete-confirm.twig', $this->menu($dialog['title'], $collection->name) + [
            'dialog' => $dialog,
        ]);
    }

    public function delete(int $collection_id, int $id): ViewResponse
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return $this->collectionNotFound();
        }

        if ($this->findOwnedField($collection_id, $id) !== null) {
            try {
                $this->deleteField->execute($id);
            } catch (CollectionSchemaException $exception) {
                return $this->wrongData($collection_id, $exception->getMessage());
            }
            $this->session->flash('success_message', __('Deleted successfully'));
        }

        redirect($this->baseUrl($collection_id));
    }

    private function findOwnedField(int $collectionId, int $id): ?ContentCollectionField
    {
        $field = $this->fieldRepository->findById($id);

        return $field !== null && $field->collection_id === $collectionId ? $field : null;
    }

    /**
     * @param list<CollectionFieldListItemDTO> $items
     * @return list<array<string, mixed>>
     */
    private function mapRows(int $collectionId, array $items): array
    {
        $base = $this->baseUrl($collectionId);

        $rows = [];
        foreach ($items as $item) {
            $editUrl = $base . '/' . $item->id . '/edit';
            $rows[] = [
                'code'       => $item->code,
                'name'       => $item->name,
                'type_label' => $this->typeLabel($item->type),
                'required'   => $item->required,
                'multiple'   => $item->multiple,
                'in_list'    => $item->inList,
                'sort'       => $item->sort,
                'edit_url'   => $editUrl,
                'actions'    => [
                    ['method' => 'get', 'url' => $editUrl, 'title' => __('Edit'), 'icon' => 'pencil', 'danger' => false, 'dialog' => false, 'fields' => []],
                    ...($item->managed ? [] : [['method' => 'get', 'url' => $base . '/' . $item->id . '/delete', 'title' => __('Delete'), 'icon' => 'trash-2', 'danger' => true, 'dialog' => true, 'fields' => []]]),
                ],
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $errors = []): ViewResponse
    {
        $this->breadcrumbs($collection);
        $title = $id !== null ? __('Edit field') : __('New field');
        $this->navChain->add($title);

        return new ViewResponse('@collections/admin/field-form.twig', $this->menu($title, $collection->name) + [
            'form_action'  => $this->baseUrl($collection->id),
            'back_url'     => $this->baseUrl($collection->id),
            'id'           => $id,
            'fields'       => $fields,
            'managed'      => $id !== null && $this->fieldRepository->findById($id)?->owner_module !== null,
            'type_options' => $this->typeOptions(),
            'errors'       => $errors,
            'default_entries' => ! empty($fields['repeat_exact']) ? $fields['default_values'] : [...$fields['default_values'], ''],
            'repeat_message' => $fields['repeat_message'] ?? '',
            'option_rows' => ! empty($fields['repeat_exact']) || count($fields['option_rows']) >= 500 ? $fields['option_rows'] : [...$fields['option_rows'], ['code' => '', 'label' => '', 'active' => true]],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request): array
    {
        return [
            'code'     => trim($request->body('code', '')),
            'role'     => $request->body('role', '') ?: null,
            'name'     => trim($request->body('name', '')),
            'type'     => $request->body('type', ''),
            'required' => $request->hasBody('required') ? 1 : 0,
            'multiple' => $request->hasBody('multiple') ? 1 : 0,
            'in_list'  => $request->hasBody('in_list') ? 1 : 0,
            'sort'     => $request->bodyInt('sort', 100),
            'repeat_exact' => true,
            'option_rows' => $this->optionsFromRequest($request),
            'target_collection_id' => $request->body('target_collection_id'),
            'relation_on_delete' => $request->body('relation_on_delete', 'restrict'),
            'hint' => $request->body('hint'), 'use_default' => $request->hasBody('use_default'),
            'default_value' => $request->body('default_value'), 'default_values' => array_values(array_filter($request->bodyList('default_values'), is_string(...))),
            'min_length' => $request->body('min_length'), 'max_length' => $request->body('max_length'),
            'min_value' => $request->body('min_value'), 'max_value' => $request->body('max_value'), 'max_values' => $request->body('max_values'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromField(ContentCollectionField $field): array
    {
        return [
            'code'     => $field->code,
            'role'     => $field->role,
            'name'     => $field->name,
            'type'     => $field->type->value,
            'required' => $field->required ? 1 : 0,
            'multiple' => $field->multiple ? 1 : 0,
            'in_list'  => $field->in_list ? 1 : 0,
            'sort'     => $field->sort,
            ...$this->settingsToFields($field->settings ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFields(): array
    {
        return [
            'code'     => '',
            'role'     => null,
            'name'     => '',
            'type'     => FieldType::String_->value,
            'required' => 0,
            'multiple' => 0,
            'in_list'  => 0,
            'sort'     => 100,
            ...$this->settingsToFields([]),
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function dtoFromFields(int $collectionId, array $fields): CollectionFieldFormDTO
    {
        return new CollectionFieldFormDTO(
            collectionId: $collectionId,
            code: (string) $fields['code'],
            role: $fields['role'] ?? null,
            name: (string) $fields['name'],
            type: FieldType::from((string) $fields['type']),
            required: (bool) $fields['required'],
            multiple: (bool) $fields['multiple'],
            inList: (bool) $fields['in_list'],
            sort: (int) $fields['sort'],
            settings: $this->settingsFromFields($fields),
        );
    }

    /** @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function settingsToFields(array $settings): array
    {
        $default = $settings['default'] ?? null;
        return [
            'hint' => $settings['hint'] ?? '',
            'target_collection_id' => $settings['target_collection_id'] ?? '',
            'relation_on_delete' => $settings['on_delete'] ?? 'restrict',
            'option_rows' => array_map(static fn (array $option): array => $option + ['active' => true], $settings['options'] ?? []), 'use_default' => $default !== null,
            'default_value' => is_string($default) ? $default : '', 'default_values' => is_array($default) ? $default : [],
            'min_length' => $settings['min_length'] ?? '', 'max_length' => $settings['max_length'] ?? '',
            'min_value' => $settings['min_value'] ?? '', 'max_value' => $settings['max_value'] ?? '', 'max_values' => $settings['max_values'] ?? '',
        ];
    }

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function settingsFromFields(array $fields): array
    {
        $default = $fields['multiple']
            ? array_values(array_filter($fields['default_values'], static fn (string $value): bool => trim($value) !== ''))
            : $fields['default_value'];
        $settings = ['hint' => $fields['hint'], 'default' => $fields['use_default'] ? $default : null];
        foreach (['min_length', 'max_length', 'min_value', 'max_value', 'max_values'] as $key) {
            $raw = trim((string) $fields[$key]);
            $integer = filter_var($raw, FILTER_VALIDATE_INT);
            $settings[$key] = $raw === '' ? null : ($integer !== false ? $integer : (in_array($key, ['min_value', 'max_value'], true) && is_numeric($raw) ? (float) $raw : $raw));
        }
        if ($fields['type'] === 'relation') {
            $target = filter_var($fields['target_collection_id'], FILTER_VALIDATE_INT);
            $settings['target_collection_id'] = $target !== false ? $target : null;
            $settings['on_delete'] = $fields['relation_on_delete'];
        }
        if ($fields['type'] === 'select') {
            $settings['options'] = array_values(array_filter($fields['option_rows'], static fn (array $option): bool => $option['code'] !== '' || $option['label'] !== ''));
        }
        return $settings;
    }

    /** @return list<array{code: string, label: string, active: bool}> */
    private function optionsFromRequest(Request $request): array
    {
        $codes = array_values($request->bodyList('option_code'));
        $labels = array_values($request->bodyList('option_label'));
        $active = array_values($request->bodyList('option_active'));
        $rows = [];
        foreach ($codes as $index => $code) {
            $rows[] = ['code' => is_string($code) ? trim($code) : '', 'label' => is_string($labels[$index] ?? null) ? trim($labels[$index]) : '', 'active' => ($active[$index] ?? '0') === '1'];
        }
        return $rows;
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

        if (FieldType::tryFrom((string) $fields['type']) === null) {
            $errors[] = __('Unknown field type');
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $options = [];
        foreach (FieldType::cases() as $case) {
            $options[$case->value] = $this->typeLabel($case);
        }

        return $options;
    }

    private function typeLabel(FieldType $type): string
    {
        return match ($type) {
            FieldType::String_  => __('String'),
            FieldType::Text     => __('Text'),
            FieldType::Html     => __('HTML'),
            FieldType::Integer  => __('Integer'),
            FieldType::Double   => __('Number (fractional)'),
            FieldType::Boolean  => __('Boolean'),
            FieldType::Date     => __('Date'),
            FieldType::Datetime => __('Date and time'),
            FieldType::File     => __('File'),
            FieldType::Select   => __('Select'),
            FieldType::Relation => __('Relation'),
        };
    }

    private function baseUrl(int $collectionId): string
    {
        return '/admin/collections/' . $collectionId . '/fields';
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
            'page_title'  => $title,
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

    private function wrongData(int $collectionId, ?string $message = null): ViewResponse
    {
        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Fields'), __('Fields')) + [
            'title'    => __('Fields'),
            'type'     => 'alert-danger',
            'message'  => $message ?? __('Wrong data'),
            'back_url' => $this->baseUrl($collectionId),
        ]);
    }
}
