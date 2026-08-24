<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Auth\Password\LegacyPasswordAudit;
use Johncms\Checker\SystemChecker;
use Johncms\Http\AdminAreaContext;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Twig\Markup;

final readonly class SystemCheckController
{
    public function __construct(
        private AdminAreaContext $adminArea,
        private NavChain $navChain,
    ) {
    }

    public function index(SystemChecker $checker, LegacyPasswordAudit $audit): ViewResponse
    {
        // This route is registered outside the guarded admin group (it answers before the panel is
        // usable), so nothing else enters the area context its layout is rendered in.
        $this->adminArea->enter();

        $title = __('System check');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/system-check.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['system_check' => true],
                'sections'   => [
                    ['title' => __('Required parameters'), 'checks' => $this->rows($checker->checkExtensions())],
                    ['title' => __('Database'), 'checks' => $this->rows($checker->checkDatabase())],
                    ['title' => __('Recommended parameters'), 'checks' => $this->rows($checker->recommendations())],
                    ['title' => __('Security'), 'checks' => $this->rows($checker->checkSecurity($audit))],
                ],
            ]
        );
    }

    /**
     * A description explains how to fix the check and may carry a link to the manual, so it is
     * markup by contract; the name and the value are plain text.
     *
     * @param array<int, array<string, mixed>> $checks
     * @return array<int, array<string, mixed>>
     */
    private function rows(array $checks): array
    {
        return array_map(
            static fn (array $check): array => [
                'name'        => (string) $check['name'],
                'value'       => (string) $check['value'],
                'description' => new Markup((string) $check['description'], 'UTF-8'),
                'error'       => (bool) $check['error'],
            ],
            $checks
        );
    }
}
