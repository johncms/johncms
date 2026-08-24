<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use Twig\Markup;

/**
 * A public item detail view.
 */
final readonly class PublicItemDetailDTO
{
    /**
     * @param list<array{label: string, values: list<string>}> $values custom field values in field order
     */
    public function __construct(
        public string $name,
        public ?Markup $previewText,
        public ?Markup $detailText,
        public array $values,
    ) {
    }
}
