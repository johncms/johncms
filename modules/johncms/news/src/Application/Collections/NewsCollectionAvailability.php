<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Extension\CollectionContentAccessProviderInterface;
use Johncms\Updates\SystemOperationContext;
use Johncms\Updates\SystemUpdateStateInterface;

final readonly class NewsCollectionAvailability implements CollectionContentAccessProviderInterface
{
    public function __construct(private CollectionLookupApiInterface $lookup, private SystemUpdateStateInterface $state, private SystemOperationContext $context)
    {
    }

    public function ready(): bool
    {
        return ($this->context->isActive() || ! $this->state->isRequired()) && $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY) !== null;
    }

    public function assertAvailable(int $collectionId): void
    {
        if ($this->context->isActive()) {
            return;
        }
        $collection = $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY);
        if ($collection?->id === $collectionId && ! $this->ready()) {
            throw new CollectionAccessDeniedException(d__('news', 'News is unavailable until system:update completes.'));
        }
    }
}
