<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\UseCases;

use Johncms\Modules\Consent\Application\DTO\ConsentDTO;
use Johncms\Modules\Consent\Domain\Models\Consent;

final readonly class UpdateConsentUseCase
{
    public function execute(Consent $consent, ConsentDTO $dto): void
    {
        $consent->update([
            'context'     => $dto->context,
            'language'    => $dto->language,
            'title'       => $dto->title,
            'text'        => $dto->text,
            'version'     => $dto->version,
            'is_required' => $dto->isRequired,
            'is_active'   => $dto->isActive,
        ]);
    }
}
