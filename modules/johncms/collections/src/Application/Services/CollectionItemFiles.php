<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Auth\CurrentUser;
use Johncms\Files\FileStore;
use Johncms\Files\StoredFileDTO;
use Johncms\Http\UploadedFileDTO;
use Johncms\Image\StoreEditorImageUseCase;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Updates\SystemOperationContext;
use Throwable;

final readonly class CollectionItemFiles implements CollectionChangeHandlerInterface
{
    public function __construct(
        private CollectionFileRepositoryInterface $links,
        private CollectionTransactionInterface $transaction,
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionItemRepositoryInterface $items,
        private FileStore $files,
        private StoreEditorImageUseCase $images,
        private EnsureCollectionContentAccessUseCase $guard,
        private CurrentUser $user,
        private SystemOperationContext $system,
    ) {
    }

    public function createContext(int $collectionId, ?int $itemId = null): string
    {
        $this->guard->execute($collectionId);
        if ($this->user->id() < 1) {
            throw $this->invalidContext();
        }
        return $this->transaction->run(function () use ($collectionId, $itemId): string {
            $this->collections->findForUpdate($collectionId) ?? throw $this->invalidContext();
            if ($itemId !== null && $this->items->findById($itemId)?->collection_id !== $collectionId) {
                throw $this->invalidContext();
            }
            $token = bin2hex(random_bytes(32));
            $this->links->createContext($token, $collectionId, $this->user->id(), $itemId);
            return $token;
        });
    }

    public function upload(int $collectionId, string $token, UploadedFileDTO $upload): StoredFileDTO
    {
        $this->guard->execute($collectionId);
        $this->requireContext($token, $collectionId, $this->user->id());
        $file = $this->images->execute($upload, 'collections/' . $collectionId);
        try {
            $this->transaction->run(function () use ($collectionId, $token, $file): void {
                $this->collections->findForUpdate($collectionId) ?? throw $this->invalidContext();
                $this->requireContext($token, $collectionId, $this->user->id(), true);
                $this->links->register($file->id, true);
                $this->links->lockAsset($file->id);
                $this->links->linkContext($token, $file->id);
            });
        } catch (Throwable $exception) {
            // The bytes predate the transaction; compensate only this newly registered upload.
            $this->files->delete($file->id);
            throw $exception;
        }
        return $file;
    }

    /** Current, authorized files of a still-open upload context. @return list<int> */
    public function contextFiles(string $token, int $collectionId, ?int $itemId, ?int $actorId): array
    {
        if ($actorId === null) {
            throw $this->invalidContext();
        }
        $context = $this->requireContext($token, $collectionId, $actorId, true);
        if ($context['item_id'] !== null && $context['item_id'] !== $itemId) {
            throw $this->invalidContext();
        }
        return $this->links->contextFiles($token, true);
    }

    public function claim(string $token, int $collectionId, int $itemId, ?int $actorId): void
    {
        if ($actorId === null) {
            throw $this->invalidContext();
        }
        $context = $this->requireContext($token, $collectionId, $actorId, true);
        if ($context['item_id'] !== null && $context['item_id'] !== $itemId) {
            throw $this->invalidContext();
        }
        $this->links->claim($token, $itemId);
    }

    /**
     * Register existing registry entries without copying bytes. The default keeps external
     * ownership; set transferOwnership only when the importer transfers exclusive responsibility.
     * @param list<int> $fileIds
     */
    public function registerExisting(int $collectionId, int $itemId, array $fileIds, bool $transferOwnership = false): void
    {
        $this->system->requireActive();
        $this->transaction->run(function () use ($collectionId, $itemId, $fileIds, $transferOwnership): void {
            $this->collections->findForUpdate($collectionId) ?? throw $this->invalidContext();
            if ($this->items->findById($itemId)?->collection_id !== $collectionId) {
                throw $this->invalidContext();
            }
            $ids = array_values(array_unique($fileIds));
            sort($ids);
            foreach ($ids as $fileId) {
                if ($fileId < 1) {
                    throw new CollectionItemFormException(['files' => [d__('collections', 'A registered file no longer exists.')]]);
                }
                $this->links->register($fileId, $transferOwnership);
                $this->links->lockAsset($fileId);
                if (! $this->files->isRegistered($fileId)) {
                    throw new CollectionItemFormException(['files' => [d__('collections', 'A registered file no longer exists.')]]);
                }
                $this->links->linkItem($itemId, $fileId);
            }
        });
    }

    /** Trusted module integration; does not grant a visitor access to file contents. @return list<int> */
    public function registeredFileIds(int $collectionId, int $itemId): array
    {
        if ($this->items->findById($itemId)?->collection_id !== $collectionId) {
            throw $this->invalidContext();
        }
        return $this->links->itemFiles($itemId);
    }

    public function withinTransaction(CollectionChangeDTO $change): void
    {
        if ($change->entity === 'item' && $change->action === 'deleted') {
            $ids = $this->links->unlinkItem($change->id);
            // Contexts open in another tab cannot attach to a deleted record and expire normally.
            $this->transaction->afterCommit(fn () => $this->removeUnreferenced($ids));
        }
    }

    public function afterCommit(CollectionChangeDTO $change): void
    {
    }

    public function cleanup(int $ttlHours = 24, int $limit = 500): int
    {
        if ($ttlHours < 1 || $limit < 1 || $limit > 10000) {
            throw new \InvalidArgumentException('Use a positive TTL and a batch size of 1..10000.');
        }
        $before = gmdate('Y-m-d H:i:s', time() - $ttlHours * 3600);
        $count = 0;
        foreach ($this->links->expiredContexts($before, $limit) as $token) {
            $this->transaction->run(function () use ($token, $before, &$count): void {
                $context = $this->links->context($token, true);
                if ($context === null || $context['created_at'] >= $before) {
                    return;
                }
                $ids = $this->links->deleteContext($token);
                $this->transaction->afterCommit(fn () => $this->removeUnreferenced($ids));
                ++$count;
            });
        }
        return $count;
    }

    /** @param list<int> $ids */
    public function removeUnreferenced(array $ids): void
    {
        foreach ($ids as $id) {
            // Serialize cleanup with new references. This callback runs after the content commit.
            $this->transaction->run(function () use ($id): void {
                $managed = $this->links->lockAsset($id);
                if ($this->links->hasLinks($id, true)) {
                    return;
                }
                if ($managed) {
                    $this->files->delete($id);
                }
                $this->links->removeAsset($id);
            });
        }
    }

    /** @return array{collection_id: int, user_id: int, item_id: ?int, created_at: string} */
    private function requireContext(string $token, int $collectionId, int $actorId, bool $lock = false): array
    {
        $context = preg_match('/^[a-f0-9]{64}$/D', $token) ? $this->links->context($token, $lock) : null;
        if ($context === null || $context['collection_id'] !== $collectionId || $context['user_id'] !== $actorId || $context['created_at'] <= gmdate('Y-m-d H:i:s', time() - 86400)) {
            throw $this->invalidContext();
        }
        if ($context['item_id'] !== null && $this->items->findById($context['item_id'])?->collection_id !== $collectionId) {
            throw $this->invalidContext();
        }
        return $context;
    }

    private function invalidContext(): CollectionItemFormException
    {
        return new CollectionItemFormException(['upload_context' => [d__('collections', 'The upload context is missing, expired or belongs to another editor. Reopen the form.')]]);
    }
}
