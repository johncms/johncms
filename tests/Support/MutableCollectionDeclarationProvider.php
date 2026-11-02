<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Modules\Collections\Application\DTO\CollectionDeclarationDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionDeclarationProviderInterface;

final class MutableCollectionDeclarationProvider implements CollectionDeclarationProviderInterface
{
    /** @param list<CollectionDeclarationDTO> $declarations */
    public function __construct(public array $declarations)
    {
    }

    public function owner(): string
    {
        return 'example/blog';
    }

    public function collections(): iterable
    {
        return $this->declarations;
    }
}
