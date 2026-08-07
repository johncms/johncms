<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Checker\SystemChecker;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Twig\Markup;

final readonly class SystemCheckController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private NavChain $navChain,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(SystemChecker $checker): ViewResponse
    {
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
