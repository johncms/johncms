<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Domain\Enums;

enum LoginStatus
{
    case Success;
    case CaptchaRequired;
    case EmailNotConfirmed;
    case ModerationPending;
    case Error;
}
