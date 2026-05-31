<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Enums;

enum ActivityType: string
{
    case Messages = 'messages';
    case Topics = 'topics';
    case Comments = 'comments';

    /**
     * Template partial suffix used to render a single row (profile::<itemType>_row).
     */
    public function itemType(): string
    {
        return match ($this) {
            self::Messages => 'message',
            self::Topics   => 'topic',
            self::Comments => 'comment',
        };
    }
}
