<?php

declare(strict_types=1);

namespace Johncms\System\View;

use Johncms\System\Http\Request;
use Johncms\Users\User;

class Theme
{
    /** @var User */
    protected $user;

    /** @var Request */
    protected $request;

    /** @var array */
    protected $themes;

    public function __construct()
    {
        $this->user = di(User::class);
        $this->request = di(Request::class);
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
        setcookie('siteTheme', $theme, time() + 60 * 60 * 24 * 365, '/');
    }

    public function getCurrentTheme(): string
    {
        $currentTheme = 'auto';
        if ($this->request->getCookie('siteTheme')) {
            $currentTheme = $this->request->getCookie('siteTheme');
        } elseif (! empty($this->user->config->skin) && in_array($this->user->config->skin, $this->themes)) {
            $currentTheme = $this->user->config->skin;
        }
        return $currentTheme;
    }
}
