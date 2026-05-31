<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\UseCases\GetBlocklistUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class BlocklistIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private GetBlocklistUseCase $getBlocklistUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(): string
    {
        $result = $this->getBlocklistUseCase->execute();

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Blacklist'), '/mail/blocklist');

        $this->render->addData([
            'title' => __('Blacklist'),
            'page_title' => __('Blacklist'),
        ]);

        return $this->render->render(
            'mail::blocklist',
            [
                'blocklist' => $result['blocklist'],
                'total' => $result['total'],
                'nav_active' => 'blocklist',
            ]
        );
    }
}
