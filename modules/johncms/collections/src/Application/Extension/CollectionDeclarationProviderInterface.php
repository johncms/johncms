<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Extension;

use Johncms\Modules\Collections\Application\DTO\CollectionDeclarationDTO;

interface CollectionDeclarationProviderInterface
{
    public function owner(): string;

    /** @return iterable<CollectionDeclarationDTO> */
    public function collections(): iterable;
}
