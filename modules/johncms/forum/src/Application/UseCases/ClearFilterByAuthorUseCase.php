<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Http\Session;

final readonly class ClearFilterByAuthorUseCase
{
    public function __construct(
        private Session $session,
    ) {
    }

    public function execute(): void
    {
        $this->session->remove(['fsort_id', 'fsort_users']);
    }
}
