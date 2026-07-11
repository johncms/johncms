<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

enum AdminTaskStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
}
