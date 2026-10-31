<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Checker\SystemCheckerInterface;
use Johncms\Checker\CheckProfile;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Utils\DateFormatterInterface;

final readonly class SystemCheckController
{
    public function __construct(
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    public function index(Request $request, SystemCheckerInterface $checker): ViewResponse
    {
        $title = __('System check');
        $this->navChain->add($title);
        $profile = $request->isMethod('POST') && $request->body('run_probes') === '1' ? CheckProfile::Extended : CheckProfile::Page;
        $report = $checker->run($profile);

        return new ViewResponse(
            '@admin/system-check.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['system_check' => true],
                'report'     => $report,
                'sections'   => $report->sections,
                'checked_at' => $this->dateFormatter->format($report->checkedAt),
            ]
        );
    }
}
