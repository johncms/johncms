<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\UseCases;

use Johncms\Modules\Consent\Domain\Models\Consent;

final readonly class DeleteConsentUseCase
{
    public function execute(Consent $consent): void
    {
        $consent->delete();
    }
}
