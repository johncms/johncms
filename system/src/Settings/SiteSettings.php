<?php

declare(strict_types=1);

namespace Johncms\Settings;

use JetBrains\PhpStorm\Pure;
use Johncms\i18n\Translator;
use Johncms\Users\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SiteSettings
{
    protected ?User $user = null;
    protected array $config;
    protected Translator $translator;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('config')['johncms'];
        $this->user = $container->get(User::class);
        $this->translator = $container->get(Translator::class);
    }

    public function __invoke(): static
    {
        return $this;
    }

    public function getTimezone(): string
    {
        return $this->user?->settings->timezone ?? $this->config['timezone'];
    }

    public function getPerPage(): int
    {
        return (int) ($this->user?->settings->perPage ?? $this->config['perPage']);
    }

    #[Pure]
    public function getLanguage(): string
    {
        return $this->translator->getLocale();
    }
}
