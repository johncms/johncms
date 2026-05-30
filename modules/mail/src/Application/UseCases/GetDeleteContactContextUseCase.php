<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Application\DTO\DeleteContactContextDTO;
use Johncms\Modules\Mail\Application\Exceptions\ContactNotFoundException;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Users\User;

final readonly class GetDeleteContactContextUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $contactId): DeleteContactContextDTO
    {
        $contact = $this->contactRepository->findContact($this->currentUser->id, $contactId);
        if ($contact === null) {
            throw new ContactNotFoundException();
        }

        // Determine back URL: if HTTP_REFERER is set, use it; otherwise default to contacts list
        $backUrl = $_SERVER['HTTP_REFERER'] ?? '/mail/contacts';
        // Sanitize back URL to prevent open redirect
        $backUrl = $this->sanitizeBackUrl($backUrl);

        return new DeleteContactContextDTO(
            contactId: $contactId,
            backUrl: $backUrl,
        );
    }

    private function sanitizeBackUrl(string $url): string
    {
        // Allow only relative URLs or same-origin absolute URLs
        if (parse_url($url, PHP_URL_HOST) !== null) {
            return '/mail/contacts';
        }
        return $url;
    }
}
