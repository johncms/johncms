<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Users\User;

abstract class BaseForumController extends BaseController
{
    protected string $moduleName = 'johncms/forum';
    protected string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = route('forum.index');
        $pageTitle = __('Forum');
        $this->navChain->add($pageTitle, $this->baseUrl);
        $this->metaTagManager->setAll($pageTitle);

        $config = di('config')['johncms'];
        $user = di(User::class);

        if (! $config['mod_forum'] && ! $user?->hasAnyRole()) {
            $error = __('Forum is closed');
        } elseif ($config['mod_forum'] === 1 && ! $user) {
            $error = __('For registered users only');
        }

        if (isset($error)) {
            echo $this->render->render(
                'system::pages/result',
                [
                    'title'    => $pageTitle,
                    'message'  => $error,
                    'type'     => 'alert-danger',
                    'back_url' => '/',
                ]
            );
            exit;
        }
    }
}
