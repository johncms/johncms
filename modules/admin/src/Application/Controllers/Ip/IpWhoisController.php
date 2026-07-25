<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\IpWhoisResultDTO;
use Johncms\Modules\Admin\Application\UseCases\GetIpWhoisUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;

final readonly class IpWhoisController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private GetIpWhoisUseCase $getIpWhoisUseCase,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(): string
    {
        $ip = (string) (filter_var($this->request->queryParam('ip'), FILTER_VALIDATE_IP) ?: '');

        $title = 'IP Whois';
        $this->navChain->add($title);

        $result = $ip !== '' ? $this->getIpWhoisUseCase->execute($ip) : new IpWhoisResultDTO('', []);

        $this->render->addData(
            [
                'title'      => __('Admin Panel'),
                'page_title' => $title,
                'sec_menu'   => ['ip_whois' => true],
            ]
        );

        return $this->render->render(
            'admin::ip_whois',
            [
                'result' => $result,
            ]
        );
    }
}
