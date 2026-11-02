<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Enums;

enum SectionDeletionMode: string
{
    case DeleteItems = 'delete';
    case MoveItems = 'move';
}
