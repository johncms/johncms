<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Modules\Admin\Application\Services\IpWhoisHighlighter;
use Johncms\Modules\Admin\Application\UseCases\GetIpWhoisUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class IpWhoisController
{
    public function __construct(
        private NavChain $navChain,
        private GetIpWhoisUseCase $getIpWhoisUseCase,
        private IpWhoisHighlighter $highlighter,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $ip = (string) (filter_var($request->queryParam('ip'), FILTER_VALIDATE_IP) ?: '');

        $title = 'IP Whois';
        $this->navChain->add($title);

        $answers = [];
        if ($ip !== '') {
            foreach ($this->getIpWhoisUseCase->execute($ip)->results as $server => $answer) {
                $answers[$server] = $this->highlighter->highlight($answer);
            }
        }

        return new ViewResponse('@admin/ip-whois.twig', [
            'title'      => __('Admin Panel'),
            'page_title' => $title,
            'sec_menu'   => ['ip_whois' => true],
            'ip'         => $ip,
            'answers'    => $answers,
        ]);
    }
}
