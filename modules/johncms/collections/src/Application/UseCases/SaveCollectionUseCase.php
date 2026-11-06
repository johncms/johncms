<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeReservedException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceSettings;
use Johncms\Modules\Collections\Application\Services\ReservedCodeCheckerInterface;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class SaveCollectionUseCase
{
    public function __construct(
        private ContentCollectionRepositoryInterface $repository,
        private CollectionCodeCacheInterface $codeCache,
        private ReservedCodeCheckerInterface $reservedCodeChecker,
        private CollectionTransactionInterface $transaction,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
        private ?ContentCollectionFieldRepositoryInterface $fields = null,
        private CollectionWorkspaceSettings $workspace = new CollectionWorkspaceSettings(),
    ) {
    }

    /**
     * Creates or updates a collection. Returns the id of the collection saved, so the panel
     * can open a new one right away.
     *
     * @throws CollectionCodeAlreadyExistsException when the code belongs to another collection
     * @throws CollectionCodeReservedException when the code collides with a reserved top-level segment
     */
    public function execute(?int $id, CollectionFormDTO $dto): int
    {
        return $this->transaction->run(function () use ($id, $dto): int {
            $current = $id !== null ? $this->repository->findForUpdate($id) : null;
            if ($current?->owner_module !== null && $current->code !== $dto->code) {
                throw new CollectionSchemaException(d__('collections', 'The identity of a module-owned collection cannot be changed here.'));
            }
            if ($current?->owner_module === null && $this->reservedCodeChecker->isReserved($dto->code)) {
                throw new CollectionCodeReservedException();
            }
            if (! preg_match('/^[a-z0-9_.-]+$/D', $dto->contentPermission)) {
                throw new CollectionSchemaException(d__('collections', 'Choose a valid content permission.'));
            }
            $existing = $this->repository->findByCode($dto->code);
            if ($existing !== null && $existing->id !== $id) {
                throw new CollectionCodeAlreadyExistsException();
            }
            $selected = $dto->searchFields ?? ($current->settings['search_fields'] ?? []);
            if (! array_is_list($selected)) {
                throw new CollectionSchemaException(d__('collections', 'Choose existing text fields for search.'));
            }
            if ($selected !== []) {
                $definitions = $this->fields?->getByCollection($id ?? 0)->keyBy('code')->all() ?? [];
                foreach ($selected as $code) {
                    if (! is_string($code) || ! isset($definitions[$code]) || ! in_array($definitions[$code]->type, [FieldType::String_, FieldType::Text, FieldType::Html], true)) {
                        throw new CollectionSchemaException(d__('collections', 'Choose existing text fields for search.'));
                    }
                }
            }
            $workspace = $dto->workspace ?? ($current->settings['workspace'] ?? []);
            $this->workspace->validate($workspace);
            $layoutFields = array_keys($dto->workspace['layout']['fields'] ?? []);
            $availableFields = $layoutFields !== [] ? $this->fields?->getByCollection($id ?? 0)->keyBy('code')->all() ?? [] : [];
            foreach ($layoutFields as $code) {
                if (! isset($availableFields[$code])) {
                    throw new CollectionSchemaException(d__('collections', 'Choose existing fields for the form layout.'));
                }
            }
            $permissions = $dto->actionPermissions ?? ($current->settings['content_actions'] ?? []);
            foreach ($permissions as $action => $permission) {
                if (! array_key_exists($action, EnsureCollectionContentAccessUseCase::actions()) || ! is_string($permission) || ! preg_match('/^[a-z0-9_.-]+$/D', $permission)) {
                    throw new CollectionSchemaException(d__('collections', 'Choose valid permissions for known content actions.'));
                }
            }
            $settings = $current->settings ?? [];
            if ($dto->actionPermissions !== null || array_key_exists('content_actions', $settings)) {
                $settings['content_actions'] = $permissions;
            }
            if ($dto->workspace !== null || array_key_exists('workspace', $settings)) {
                $settings['workspace'] = $workspace;
            }
            $before = $current !== null ? new CollectionChangeStateDTO(null, $current->active, $current->code) : null;
            $attributes = [
            'code'        => $dto->code,
            'name'        => $current?->owner_module !== null ? $current->getRawOriginal('name') : $dto->name,
            'description' => $dto->description,
            'active'      => $dto->active,
            'public'      => $dto->public,
            'sort'        => $dto->sort,
            'settings'    => array_replace($settings, [
                'has_sections' => $dto->hasSections,
                'per_page'     => $dto->perPage,
                'search_fields' => array_values(array_unique($selected)),
                '_content_permission' => $current?->owner_module !== null ? ($current->settings['_content_permission'] ?? 'collections.manage') : $dto->contentPermission,
            ]),
            ];

            if ($current !== null) {
                $this->repository->update($id, $attributes);
                $this->changes->dispatch(new CollectionChangeDTO('collection', 'saved', $id, $id, $before, new CollectionChangeStateDTO(null, $dto->active, $dto->code)));
                $this->transaction->afterCommit(fn () => $this->codeCache->invalidate());

                return $id;
            }

            $collection = $this->repository->create($attributes);
            $this->changes->dispatch(new CollectionChangeDTO('collection', 'saved', $collection->id, $collection->id, null, new CollectionChangeStateDTO(null, $dto->active, $dto->code)));
            $this->transaction->afterCommit(fn () => $this->codeCache->invalidate());

            return $collection->id;
        });
    }
}
