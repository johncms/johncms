<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Extension;

use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;

interface CollectionOwnerUrlProviderInterface
{
    public function owner(): string;

    public function collectionUrl(CollectionRecordDTO $collection): ?string;

    public function itemUrl(CollectionRecordDTO $collection, CollectionItemDTO $item): ?string;

    public function sectionUrl(CollectionRecordDTO $collection, CollectionSectionDTO $section): ?string;

    public function previewUrl(CollectionRecordDTO $collection, CollectionItemDTO $item): ?string;

    public function settingsUrl(CollectionRecordDTO $collection): ?string;
}
