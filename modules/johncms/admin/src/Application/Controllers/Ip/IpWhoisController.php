<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\UseCases\GetIpWhoisUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\IpRegistryUnavailableException;
use Johncms\NavChain;

final readonly class IpWhoisController
{
    public function __construct(
        private NavChain $navChain,
        private GetIpWhoisUseCase $getIpWhoisUseCase,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $query = trim($request->queryParam('ip'));
        $ip = (string) (filter_var($query, FILTER_VALIDATE_IP) ?: '');

        $title = __('IP Whois');
        $this->navChain->add($title);

        $state = 'empty';
        $registration = null;
        if ($query !== '' && $ip === '') {
            $state = 'invalid';
        } elseif ($ip !== '' && ! $this->getIpWhoisUseCase->isPublic($ip)) {
            $state = 'private';
        } elseif ($ip !== '') {
            try {
                $registration = $this->getIpWhoisUseCase->execute($ip);
                $state = $registration === null ? 'unknown' : 'found';
            } catch (IpRegistryUnavailableException) {
                $state = 'unavailable';
            }
        }

        return new ViewResponse('@admin/ip-whois.twig', [
            'title'        => $title,
            'page_title'   => $title,
            'usr_menu'     => ['ip_whois' => true],
            'query'        => $query,
            'ip'           => $ip,
            'state'        => $state,
            'registration' => $registration,
            'search_url'   => $ip === '' ? '' : '/admin/ip-search?ip=' . rawurlencode($ip),
        ]);
    }
}
