<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Mail\Application\UseCases\GetBlocklistUseCase;
use Johncms\NavChain;

final readonly class BlocklistIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private GetBlocklistUseCase $getBlocklistUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(): ViewResponse
    {
        $result = $this->getBlocklistUseCase->execute();

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Blacklist'), '/mail/blocklist');

        return new ViewResponse(
            '@mail/public/blocklist.twig',
            [
                'title' => __('Blacklist'),
                'page_title' => __('Blacklist'),
                'blocklist' => $result['blocklist'],
                'total' => $result['total'],
                'nav_active' => 'blocklist',
            ]
        );
    }
}
