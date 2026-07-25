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

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Help\Application\UseCases\GetMySmiliesUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class MySmiliesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private GetMySmiliesUseCase $mySmilies,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(): Response
    {
        if (! $this->currentUser->isValid()) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Access denied'),
                'type'          => 'alert-danger',
                'message'       => __('You are not logged in'),
                'back_url'      => '/help/smilies/',
                'back_url_name' => __('Back'),
            ]), Response::HTTP_FORBIDDEN);
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
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $title,
            'description' => $meta->description,
        ]);

        return new Response($this->render->render('help::my_smiles_list', [
            'data' => [
                'items'       => $this->mySmilies->getPage($pagination->getPerPage(), $pagination->getOffset()),
                'total'       => $pagination->getTotal(),
                'pagination'  => $pagination->render(),
                'form_action' => '/help/smilies/set/?page=' . $pagination->getCurrentPage(),
                'back_url'    => '/help/smilies/',
            ],
        ]));
    }
}
