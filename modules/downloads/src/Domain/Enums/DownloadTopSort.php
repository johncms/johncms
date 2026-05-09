<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Enums;

enum DownloadTopSort
{
    case Popular;
    case Downloaded;
    case Commented;

    public function column(): string
    {
        return match ($this) {
            self::Popular    => 'rate',
            self::Downloaded => 'field',
            self::Commented  => 'comm_count',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::Popular    => 'popular',
            self::Downloaded => 'downloaded',
            self::Commented  => 'commented',
        };
    }

    public static function fromSlug(string $slug): self
    {
        return match ($slug) {
            'downloaded' => self::Downloaded,
            'commented'  => self::Commented,
            default      => throw new \Johncms\Exceptions\PageNotFoundException(),
        };
    }

    public static function fromId(int $id): self
    {
        return match ($id) {
            1       => self::Downloaded,
            2       => self::Commented,
            default => self::Popular,
        };
    }
}
