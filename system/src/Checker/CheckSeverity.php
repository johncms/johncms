<?php

declare(strict_types=1);

namespace Johncms\Checker;

enum CheckSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
}
