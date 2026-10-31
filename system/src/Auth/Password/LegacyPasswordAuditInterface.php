<?php

declare(strict_types=1);

namespace Johncms\Auth\Password;

interface LegacyPasswordAuditInterface
{
    public function countAccountsOnLegacyHash(): int;

    public function countAccounts(): int;
}
