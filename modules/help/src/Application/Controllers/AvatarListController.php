<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Help\Application\UseCases\GetAvatarsUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\Response;

final readonly class AvatarListController
{
    public function __construct(
        private NavChain $navChain,
        private GetAvatarsUseCase $avatars,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(string $id): ViewResponse
    {
        if (! $this->avatars->directoryExists($id)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => '/help/avatars/',
                    'back_url_name' => __('Back'),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $title = $this->avatars->directoryTitle($id);

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add(__('Avatars'), '/help/avatars/');
        $this->navChain->add($title);

        $pagination = $this->paginationFactory->create($this->avatars->count($id), GetAvatarsUseCase::PER_PAGE);

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        return new ViewResponse(
            '@help/public/avatar-list.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'items'      => $this->avatars->getPage($id, $pagination->getPerPage(), $pagination->getOffset()),
                'total'      => $pagination->getTotal(),
                'pagination' => $pagination->hasPages() ? $pagination->render() : null,
                'back_url'   => '/help/avatars/',
            ]
        );
    }
}
