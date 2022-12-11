<?php

declare(strict_types=1);

namespace Johncms\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

abstract class AbstractBladeComponent extends Component
{
    abstract public function render(): View;
}
