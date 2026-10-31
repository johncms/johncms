<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Auth\Password\LegacyPasswordAuditInterface;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;

final readonly class LegacyPasswordsCheck implements SystemCheckInterface
{
    public function __construct(private LegacyPasswordAuditInterface $audit)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('security.legacy_passwords', d__('system', 'Accounts with an outdated password hash'), d__('system', 'Security'), 'shield', order: 80);
    }

    public function run(): array
    {
        $legacy = $this->audit->countAccountsOnLegacyHash();

        return [new CheckResultDTO(
            'security.legacy_passwords',
            self::definition()->name,
            sprintf('%d / %d', $legacy, $this->audit->countAccounts()),
            $legacy > 0 ? CheckStatus::Failed : CheckStatus::Passed,
            CheckSeverity::Info,
            d__('system', 'These accounts predate the current password hashing and their owners have not signed in since.
Each one is converted automatically the next time its owner signs in — nobody has to reset anything.
The number is shown so that support for the old scheme is not removed while it is still in use.'),
        )];
    }
}
