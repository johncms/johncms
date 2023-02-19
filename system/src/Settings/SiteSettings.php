<?php

declare(strict_types=1);

namespace Johncms\Settings;

use Johncms\i18n\Translator;
use Johncms\Users\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SiteSettings
{
    protected array $config;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        ContainerInterface $container,
        protected Translator $translator,
        protected ?User $user = null,
    ) {
        $this->config = $container->get('config')['johncms'];
    }

    public function getTimezone(): string
    {
        return $this->user?->settings->timezone ?? $this->config['timezone'];
    }

    public function getPerPage(): int
    {
        return (int) ($this->user?->settings->perPage ?? $this->config['perPage']);
    }

    public function getLanguage(): string
    {
        return $this->translator->getLocale();
    }
}
