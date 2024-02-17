<?php

declare(strict_types=1);

namespace Johncms\Forms\Inputs;

class InputDateTime extends AbstractInput
{
    public string $type = 'date';
    public bool $showTime = false;

    public function setShowTime(bool $showTime = true): static
    {
        $this->showTime = $showTime;
        return $this;
    }
}
