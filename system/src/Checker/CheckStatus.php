<?php

declare(strict_types=1);

namespace Johncms\Checker;

enum CheckStatus: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Unavailable = 'unavailable';
}
