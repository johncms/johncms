<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Application\DTO\ClearConversationContextDTO;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Users\User;

final readonly class GetClearConversationContextUseCase
{
    public function execute(int $contactId): ClearConversationContextDTO
    {
        $contact = User::query()->find($contactId);
        if ($contact === null) {
            throw new UserNotFoundException();
        }

        return new ClearConversationContextDTO(
            contactId: $contactId,
            backUrl: '/mail/write/' . $contactId,
        );
    }
}
