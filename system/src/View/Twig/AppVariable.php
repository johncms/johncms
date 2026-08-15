<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

use Johncms\Auth\CurrentUser;
use Johncms\Http\CurrentPage;
use Johncms\Security\Csrf;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;
use Johncms\View\ColorScheme;
use Johncms\View\Theme\ThemeDTO;
use Johncms\View\Theme\ThemeRepositoryInterface;
use Johncms\View\ViewEnvironment;
use InvalidArgumentException;

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
     * The CSRF token arrives as a closure: building it costs a session, and a page that never
     * mentions it should pay nothing. The current user is lazy on its own — the profile behind
     * app.user is queried on the first template asking for it.
     *
     * @param callable(): Csrf $csrf
     */
    public function __construct(
        private ViewEnvironment $environment,
        private CurrentPage $currentPage,
        private ColorScheme $colorScheme,
        private ThemeRepositoryInterface $themes,
        private CurrentUser $currentUser,
        private mixed $csrf,
        private Translator $translator,
    ) {
    }

    /**
     * Twig looks an attribute up by the exact name it is written with, so app.color_scheme would
     * never find getColorScheme(). Templates are written in snake_case, and these two map such a
     * name onto the getter behind it.
     */
    public function __isset(string $name): bool
    {
        return $this->accessor($name) !== null;
    }

    public function __get(string $name): mixed
    {
        $accessor = $this->accessor($name);

        if ($accessor === null) {
            throw new InvalidArgumentException('The app variable has no "' . $name . '" fact.');
        }

        return $this->{$accessor}();
    }

    private function accessor(string $name): ?string
    {
        $camelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

        foreach ([lcfirst($camelCase), 'get' . $camelCase, 'is' . $camelCase] as $method) {
            if (method_exists($this, $method)) {
                return $method;
            }
        }

        return null;
    }

    public function getUser(): User
    {
        return $this->currentUser->user();
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function getColorScheme(): string
    {
        return $this->colorScheme->getCurrentScheme();
    }

    public function isDarkScheme(): bool
    {
        return $this->colorScheme->isDarkScheme();
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
