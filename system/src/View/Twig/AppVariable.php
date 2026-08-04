<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

use Johncms\Http\CurrentPage;
use Johncms\Security\Csrf;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;
use Johncms\View\ColorScheme;
use Johncms\View\Theme\ThemeDTO;
use Johncms\View\Theme\ThemeRepositoryInterface;
use Johncms\View\ViewEnvironment;

/**
 * The single global a template gets: facts about the page being served.
 *
 * Facts, not services — a template never reaches the request, the container or the configuration
 * array. Everything is resolved on access, so a page that does not mention app.user does not
 * build the user.
 */
final readonly class AppVariable
{
    /**
     * The user and the CSRF token arrive as closures: building them costs a database query and a
     * session, and a page that never mentions them should pay neither.
     *
     * @param callable(): User $user
     * @param callable(): Csrf $csrf
     */
    public function __construct(
        private ViewEnvironment $environment,
        private CurrentPage $currentPage,
        private ColorScheme $colorScheme,
        private ThemeRepositoryInterface $themes,
        private mixed $user,
        private mixed $csrf,
        private Translator $translator,
    ) {
    }

    public function getUser(): User
    {
        return ($this->user)();
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function getColorScheme(): string
    {
        return $this->colorScheme->getCurrentScheme();
    }

    public function getCsrfToken(): string
    {
        return ($this->csrf)()->getToken();
    }

    public function isHomePage(): bool
    {
        return $this->currentPage->isHomePage();
    }

    public function getEnvironment(): string
    {
        return $this->environment->value;
    }

    public function getTheme(): ?ThemeDTO
    {
        return $this->themes->find((string) config('johncms.skindef', 'default'));
    }

    public function getCmsVersion(): string
    {
        return CMS_VERSION;
    }

    public function isDebug(): bool
    {
        return DEBUG;
    }
}
