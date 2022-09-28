<?php

declare(strict_types=1);

namespace Johncms\System\View;

use Johncms\System\Http\Session;
use Johncms\Users\User;

class Theme
{
    /** @var User */
    protected $user;

    /** @var Session */
    protected $session;

    /** @var array */
    protected $themes;

    public function __construct()
    {
        $this->user = di(User::class);
        $this->session = di(Session::class);
        $this->themes = [
            'dark',
            'light',
            'auto',
        ];
    }

    public function __invoke(): Theme
    {
        return new self();
    }

    public function setTheme(string $theme): void
    {
        if (! in_array($theme, $this->themes)) {
            $theme = 'auto';
        }
        $this->session->set('siteTheme', $theme);
    }

    public function getCurrentTheme(): string
    {
        $currentTheme = 'auto';
        if ($this->session->has('siteTheme')) {
            $currentTheme = $this->session->get('siteTheme', $currentTheme);
        } elseif (! empty($this->user->config->skin)) {
            $currentTheme = $this->user->config->skin;
        }
        return $currentTheme;
    }
}
