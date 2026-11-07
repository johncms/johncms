<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use InvalidArgumentException;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\UploadLimits;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldListItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionFieldCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionFilePolicy;
use Johncms\Modules\Collections\Application\Services\CollectionFieldFormChoices;
use Johncms\Modules\Collections\Application\Services\CollectionFormLayout;
use Symfony\Component\HttpFoundation\JsonResponse;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionFieldUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionFieldsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionFieldUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\NavChain;

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
        private CollectionFieldFormChoices $choices,
        private UploadLimits $uploadLimits,
        private CollectionFormLayout $layout = new CollectionFormLayout(),
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

        $sorts = $this->fieldRepository->getByCollection($collection_id)->pluck('sort');

        return $this->renderForm($collection, null, ['sort' => $sorts->isEmpty() ? 100 : $sorts->max() + 10] + $this->defaultFields());
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
        $current = $id !== null ? $this->findOwnedField($collection_id, $id) : null;
        $fields['legacy_length_limits'] = $current !== null && $current->type->value === $fields['type']
            && $fields['type'] === 'select'
            && (isset($current->settings['min_length']) || isset($current->settings['max_length']));
        if ($request->hasBody('default_type') && $this->defaultGroup($request->body('default_type')) !== $this->defaultGroup($fields['type'])) {
            $fields['default_value'] = '';
            $fields['default_values'] = [];
            $fields['use_default'] = false;
        }
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($collection, $id, $fields, array_values($errors), $errors);
        }

        try {
            $this->saveField->execute($id, $this->dtoFromFields($collection_id, $fields));
        } catch (CollectionSchemaException $exception) {
            return $this->renderForm($collection, $id, $fields, [$exception->getMessage()], $exception->errors);
        } catch (CollectionFieldCodeAlreadyExistsException) {
            return $this->renderForm($collection, $id, $fields, [__('A field with this code already exists')], ['code' => __('A field with this code already exists')]);
        }

        $this->session->flash('success_message', $id !== null ? __('Changes saved') : __('Created successfully'));
        redirect($this->baseUrl($collection_id));
    }

    public function defaultChoices(Request $request, int $collection_id): JsonResponse
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return new JsonResponse(['error' => d__('collections', 'Collection not found.')], 404);
        }
        try {
            $fields = $this->fieldsFromRequest($request);
            return new JsonResponse(['options' => $this->defaultOptions($collection_id, $fields)], 200, ['Cache-Control' => 'no-store']);
        } catch (CollectionSchemaException | InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        }
    }

    /** @param array<string, mixed> $fields
     * @return list<array{code: string, label: string}>
     */
    private function defaultOptions(int $collectionId, array $fields): array
    {
        if ($fields['type'] !== FieldType::Relation->value) {
            return [];
        }
        return $this->choices->relationValues($collectionId, $this->settingsFromFields($fields), $fields['default_query'] ?? '', array_values(array_unique([$fields['default_value'], ...$fields['default_values']])));
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
     * @param array<string, string> $fieldErrors
     */
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $errors = [], array $fieldErrors = []): ViewResponse
    {
        $fields = $this->normalizeRelationFields($fields);
        $field = $id !== null ? $this->fieldRepository->findById($id) : null;
        $locked = $field !== null && $this->fieldRepository->hasValues($field->id);
        $this->breadcrumbs($collection);
        $title = $id !== null ? __('Edit field') : __('New field');
        $this->navChain->add($title);

        $defaultOptions = [];
        $lookupError = '';
        if ($fields['type'] === FieldType::Relation->value) {
            try {
                $defaultOptions = $this->defaultOptions($collection->id, $fields);
            } catch (CollectionSchemaException | InvalidArgumentException | CollectionAccessDeniedException $exception) {
                $lookupError = $exception->getMessage();
            }
        }
        return new ViewResponse('@collections/admin/field-form.twig', $this->menu($title, $collection->name) + [
            'form_action'  => $this->baseUrl($collection->id),
            'file_type_groups' => $this->fileTypeGroups(),
            'server_upload_limit' => $this->uploadLimits->maxUploadBytes(),
            'collection_options' => $this->choices->collections(),
            'default_options' => $defaultOptions,
            'default_query' => $fields['default_query'] ?? '',
            'file_size_unit' => $fields['file_size_unit'] ?? '1',
            'default_lookup_error' => $lookupError,
            'structure_locked' => $locked,
            'owner_module' => $field->owner_module ?? '',
            'back_url'     => $this->baseUrl($collection->id),
            'id'           => $id,
            'fields'       => $fields,
            'managed'      => $id !== null && $this->fieldRepository->findById($id)?->owner_module !== null,
            'type_options' => $this->typeOptions(),
            'role_options' => $this->roleOptions($fields['role'] ?? null),
            'role_available' => ($fields['role'] ?? null) !== null || in_array($fields['type'], ['string', 'text', 'html'], true),
            'layout_sort' => isset($this->layout->settings($collection)['fields'][$fields['code']]['sort']),
            'slug_linked' => $fields['slug_linked'] ?? ($id === null && $fields['code'] === ''),
            'errors'       => $errors,
            'field_errors' => $fieldErrors,
            'default_entries' => $fields['default_values'] ?: [''],
            'option_rows' => $fields['option_rows'] ?: [['code' => '', 'label' => '', 'active' => true]],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request): array
    {
        $fields = [
            'code'     => trim($request->body('code', '')),
            'role'     => $request->body('role', '') ?: null,
            'name'     => trim($request->body('name', '')),
            'type'     => $request->body('type', ''),
            'required' => $request->hasBody('required') ? 1 : 0,
            'multiple' => $request->hasBody('multiple') ? 1 : 0,
            'in_list'  => $request->hasBody('in_list') ? 1 : 0,
            'sort'     => $request->bodyInt('sort', 100),
            'default_query' => $request->body('default_query'),
            'option_rows' => $this->optionsFromRequest($request),
            'target_collection_id' => $request->body('target_collection_id'),
            'relation_on_delete' => $request->body('relation_on_delete', 'restrict'),
            'max_file_bytes' => $request->body('max_file_bytes'),
            'file_size_unit' => $request->body('file_size_unit', '1'),
            'file_constraints_present' => $request->hasBody('file_constraints_present'),
            'allowed_mime_types' => array_values(array_filter($request->bodyList('allowed_mime_types'), is_string(...))),
            'date_default_mode' => $request->body('date_default_mode', $request->hasBody('use_default') ? 'fixed' : 'none'),
            'date_default_mode_present' => $request->hasBody('date_default_mode'),
            'slug_linked' => $request->hasBody('slug_linked'),
            'hint' => $request->body('hint'), 'use_default' => $request->hasBody('use_default'),
            'default_value' => $request->body('default_value'),
            'default_values' => array_values(array_filter($request->bodyList('default_values'), is_string(...))),
            'min_length' => $request->body('min_length'), 'max_length' => $request->body('max_length'),
            'min_value' => $request->body('min_value'), 'max_value' => $request->body('max_value'), 'max_values' => $request->body('max_values'),
        ];
        return $this->normalizeRelationFields($fields);
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
            'legacy_length_limits' => $field->type === FieldType::Select
                && (isset($field->settings['min_length']) || isset($field->settings['max_length'])),
        ];
    }

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function normalizeRelationFields(array $fields): array
    {
        if ($fields['type'] === 'relation') {
            $fields['default_value'] = trim($fields['default_value']);
            $fields['default_values'] = array_values(array_unique(array_filter(array_map(trim(...), $fields['default_values']), static fn (string $value): bool => $value !== '')));
            $fields['use_default'] = $fields['multiple'] ? $fields['default_values'] !== [] : $fields['default_value'] !== '';
        }
        return $fields;
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
            'allowed_mime_types' => CollectionFilePolicy::DEFAULT_FORMATS,
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
            'date_default_mode' => ($settings['date_preset'] ?? null) === 'current' ? 'current' : ($default !== null ? 'fixed' : 'none'),
            'hint' => $settings['hint'] ?? '',
            'target_collection_id' => $settings['target_collection_id'] ?? '',
            'relation_on_delete' => $settings['on_delete'] ?? 'restrict',
            ...$this->fileSize($settings['max_file_bytes'] ?? CollectionFilePolicy::DEFAULT_MAX_BYTES),
            'allowed_mime_types' => $settings['allowed_mime_types'] ?? array_keys(CollectionFilePolicy::TYPES),
            'option_rows' => array_map(static fn (array $option): array => $option + ['active' => true], $settings['options'] ?? []), 'use_default' => $default !== null || ($settings['date_preset'] ?? null) === 'current',
            'default_value' => is_string($default) ? $default : '', 'default_values' => is_array($default) ? $default : [],
            'legacy_length_limits' => false,
            'min_length' => $settings['min_length'] ?? '', 'max_length' => $settings['max_length'] ?? '',
            'min_value' => $settings['min_value'] ?? '', 'max_value' => $settings['max_value'] ?? '', 'max_values' => $settings['max_values'] ?? '',
        ];
    }

    /** @return array{max_file_bytes: int, file_size_unit: string} The size in the largest unit that holds it exactly. */
    private function fileSize(int $bytes): array
    {
        foreach ([1073741824, 1048576, 1024] as $unit) {
            if ($bytes % $unit === 0) {
                return ['max_file_bytes' => intdiv($bytes, $unit), 'file_size_unit' => (string) $unit];
            }
        }
        return ['max_file_bytes' => $bytes, 'file_size_unit' => '1'];
    }

    /** @return list<array{label: string, types: array<string, string>}> */
    private function fileTypeGroups(): array
    {
        $labels = [
            'images'        => __('Images'),
            'documents'     => __('Documents'),
            'spreadsheets'  => __('Spreadsheets'),
            'presentations' => __('Presentations'),
            'archives'      => __('Archives'),
            'audio'         => __('Audio'),
            'video'         => __('Video'),
        ];
        $groups = [];
        foreach (CollectionFilePolicy::GROUPS as $key => $mimes) {
            $groups[] = ['label' => $labels[$key], 'types' => array_intersect_key(CollectionFilePolicy::TYPES, array_flip($mimes))];
        }
        return $groups;
    }

    /** Types whose default values mean the same thing share a group. */
    private function defaultGroup(string $type): string
    {
        return in_array($type, ['string', 'text', 'html'], true) ? 'text' : $type;
    }

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function settingsFromFields(array $fields): array
    {
        $default = $fields['multiple']
            ? array_values(array_filter($fields['default_values'], static fn (string $value): bool => trim($value) !== ''))
            : $fields['default_value'];
        if ($fields['type'] === 'relation' && $fields['multiple']) {
            $default = array_values(array_unique($default));
        }
        $useDefault = $fields['type'] === 'relation' ? ($fields['multiple'] ? $default !== [] : trim($default) !== '') : $fields['use_default'];
        $settings = ['hint' => $fields['hint'], 'default' => $useDefault && $fields['type'] !== 'file' ? $default : null];
        $settings['date_preset'] = null;
        if (in_array($fields['type'], ['date', 'datetime'], true) && ($fields['date_default_mode_present'] ?? true)) {
            $mode = $fields['date_default_mode'];
            if (! in_array($mode, ['none', 'fixed', 'current'], true)) {
                throw new CollectionSchemaException(message: d__('collections', 'Choose a valid initial date value.'), errors: ['date_default_mode' => d__('collections', 'Choose a valid initial date value.')]);
            }
            $settings['date_preset'] = $mode === 'current' ? 'current' : null;
            $settings['default'] = $mode === 'fixed' ? $default : null;
        }

        $lengthLimits = in_array($fields['type'], ['string', 'text', 'html'], true)
            || ($fields['type'] === 'select' && ($fields['legacy_length_limits'] ?? false));
        $numericLimits = in_array($fields['type'], ['integer', 'double'], true);
        foreach (['min_length', 'max_length', 'min_value', 'max_value', 'max_values'] as $key) {
            $applicable = match ($key) {
                'min_length', 'max_length' => $lengthLimits,
                'min_value', 'max_value' => $numericLimits,
                'max_values' => (bool) $fields['multiple'],
            };
            if (! $applicable) {
                $settings[$key] = null;
                continue;
            }
            $raw = trim((string) $fields[$key]);
            $integer = filter_var($raw, FILTER_VALIDATE_INT);
            $settings[$key] = $raw === '' ? null : ($integer !== false ? $integer : (in_array($key, ['min_value', 'max_value'], true) && is_numeric($raw) ? (float) $raw : $raw));
        }
        if ($fields['type'] === 'file' && ($fields['file_constraints_present'] ?? true)) {
            $bytes = filter_var($fields['max_file_bytes'], FILTER_VALIDATE_INT);
            $size = $fields['max_file_bytes'];
            $unit = $fields['file_size_unit'] ?? '1';
            if ($unit !== '1') {
                $scaled = is_numeric($size) && in_array($unit, ['1024', '1048576', '1073741824'], true) ? (float) $size * (int) $unit : -1;
                $bytes = is_finite($scaled) && $scaled >= 1 && $scaled <= 1073741824 && floor($scaled) === $scaled ? (int) $scaled : false;
            }
            $settings['max_file_bytes'] = $size === '' ? null : ($bytes !== false ? $bytes : ($unit !== '1' ? 'invalid file size' : $size));
            $settings['allowed_mime_types'] = $fields['allowed_mime_types'];
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
     * @return array<string, string>
     */
    private function validate(array $fields): array
    {
        $errors = [];
        foreach (['code', 'name'] as $key) {
            if ($fields[$key] === '') {
                $errors[$key] = __('The required fields are not filled');
            }
        }

        if ($fields['code'] !== '' && ! preg_match('/^[a-z0-9_-]+$/', (string) $fields['code'])) {
            $errors['code'] = __('The code may contain only lowercase latin letters, digits, hyphen and underscore');
        }

        if (FieldType::tryFrom((string) $fields['type']) === null) {
            $errors['type'] = __('Unknown field type');
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

    /** @return list<array{code: string, label: string, types: list<string>, multiple: bool}> */
    private function roleOptions(?string $current): array
    {
        $text = ['string', 'text', 'html'];
        $roles = [
            ['code' => 'meta_title', 'label' => __('Page title'), 'types' => $text, 'multiple' => false],
            ['code' => 'meta_description', 'label' => __('Meta description'), 'types' => $text, 'multiple' => false],
            ['code' => 'meta_keywords', 'label' => __('Meta keywords'), 'types' => $text, 'multiple' => false],
            ['code' => 'tags', 'label' => __('Tags'), 'types' => ['string'], 'multiple' => true],
        ];
        if ($current !== null && ! in_array($current, array_column($roles, 'code'), true)) {
            $roles[] = ['code' => $current, 'label' => $current, 'types' => array_column(FieldType::cases(), 'value'), 'multiple' => false];
        }
        return $roles;
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
