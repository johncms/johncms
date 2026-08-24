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

use Johncms\Auth\CurrentUser;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Help\Application\UseCases\GetMySmiliesUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\Response;

final readonly class MySmiliesController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private GetMySmiliesUseCase $mySmilies,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        if (! $this->currentUser->isValid()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Access denied'),
                    'type'          => 'alert-danger',
                    'message'       => __('You are not logged in'),
                    'back_url'      => '/help/smilies/',
                    'back_url_name' => __('Back'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $title = __('My smilies');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add(__('Smiles'), '/help/smilies/');
        $this->navChain->add($title);

        $pagination = $this->paginationFactory->create($this->mySmilies->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($title . ' — ' . __('Smiles'), $pagination->getCurrentPage());

        return new ViewResponse(
            '@help/public/my-smilies-list.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $title,
                'description' => $meta->description,
                'items'       => $this->mySmilies->getPage($pagination->getPerPage(), $pagination->getOffset()),
                'total'       => $pagination->getTotal(),
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'form_action' => '/help/smilies/set/?page=' . $pagination->getCurrentPage(),
                'back_url'    => '/help/smilies/',
            ]
        );
    }
}
