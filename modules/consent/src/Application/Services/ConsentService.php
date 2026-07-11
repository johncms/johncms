<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Modules\Consent\Domain\Models\Consent;
use Johncms\Modules\Consent\Domain\Models\ConsentLog;
use Johncms\Modules\Consent\Domain\Repository\ConsentLogRepositoryInterface;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\System\i18n\Translator;

/**
 * Programmatic API for the consent subsystem.
 *
 * Other modules depend on this service (not on the repositories directly) to obtain the
 * consents that must be shown on a given form and to record acceptance into the log.
 */
final readonly class ConsentService
{
    public function __construct(
        private ConsentRepositoryInterface $consents,
        private ConsentLogRepositoryInterface $log,
        private Translator $translator,
    ) {
    }

    /**
     * Active consents that must be displayed for the given form context (e.g. "register").
     *
     * Consents are resolved for the current interface language; when none exist for it,
     * the site default language is used as a fallback.
     *
     * @return Collection<int, Consent>
     */
    public function getActiveConsents(string $context): Collection
    {
        $consents = $this->consents->getActiveByContext($context, $this->translator->getLocale());

        if ($consents->isEmpty()) {
            $defaultLanguage = (string) (config('johncms')['lng'] ?? 'en');
            if ($defaultLanguage !== $this->translator->getLocale()) {
                $consents = $this->consents->getActiveByContext($context, $defaultLanguage);
            }
        }

        return $consents;
    }

    public function getConsent(int $id): ?Consent
    {
        return $this->consents->findById($id);
    }

    /**
     * Record that a consent has been accepted.
     *
     * When $version is null, the current version of the consent is used as a snapshot.
     */
    public function logAcceptance(
        int $consentId,
        ?int $userId,
        string $ipAddress,
        ?string $version = null,
    ): ConsentLog {
        if ($version === null) {
            $consent = $this->consents->findById($consentId);
            $version = $consent?->version ?? '';
        }

        return $this->log->create([
            'user_id'     => $userId,
            'consent_id'  => $consentId,
            'version'     => $version,
            'ip_address'  => $ipAddress,
            'accepted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
