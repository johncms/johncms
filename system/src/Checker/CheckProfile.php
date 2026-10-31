<?php

declare(strict_types=1);

namespace Johncms\Checker;

enum CheckProfile: string
{
    case Page = 'page';
    case Dashboard = 'dashboard';
    case Installer = 'installer';
    case Extended = 'extended';
}
