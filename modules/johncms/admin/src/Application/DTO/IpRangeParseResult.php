<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class IpRangeParseResult
{
    /**
     * @param int|null $from Начало диапазона (ip2long) или null при ошибке
     * @param int|null $to   Конец диапазона (ip2long) или null при ошибке
     * @param list<string> $errors Сообщения об ошибках разбора
     */
    public function __construct(
        public ?int $from,
        public ?int $to,
        public array $errors = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [] && $this->from !== null && $this->to !== null;
    }
}
