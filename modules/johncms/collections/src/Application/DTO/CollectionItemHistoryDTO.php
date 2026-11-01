<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class CollectionItemHistoryDTO
{
    public function __construct(
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?int $createdBy = null,
        public ?int $updatedBy = null,
        public int $viewCount = 0,
    ) {
    }

    /** @return array<string, int|string|null> */
    public function attributes(): array
    {
        foreach ([$this->createdBy, $this->updatedBy, $this->viewCount] as $number) {
            if ($number !== null && ($number < 0 || $number > 2147483647)) {
                throw new InvalidArgumentException('Historical authors and views must fit a non-negative signed integer.');
            }
        }
        return [
            'created_at' => self::timestamp($this->createdAt), 'updated_at' => self::timestamp($this->updatedAt),
            'created_by' => $this->createdBy, 'updated_by' => $this->updatedBy, 'view_count' => $this->viewCount,
        ];
    }

    public static function timestamp(DateTimeImmutable $date): string
    {
        $value = $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        if (strlen($value) !== 19 || $value < '1000-01-01 00:00:00' || $value > '9999-12-31 23:59:59') {
            throw new InvalidArgumentException('Historical timestamps must fit the database datetime range.');
        }
        return $value;
    }
}
