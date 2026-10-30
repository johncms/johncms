<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\UseCases\GetSmileyFoldersUseCase;
use Johncms\Modules\Admin\Application\UseCases\RebuildSmiliesCacheUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Smilies\SmiliesCacheWriteException;
use Johncms\Http\Request;

final readonly class EmoticonsController
{
    private const URL = '/admin/emoticons';

    public function __construct(
        private NavChain $navChain,
        private RebuildSmiliesCacheUseCase $rebuildSmiliesCacheUseCase,
        private Session $session,
        private GetSmileyFoldersUseCase $getFolders,
    ) {
    }

    /**
     * The site does not look at the folders on its requests: a picture put in one is picked up
     * here, when the page is opened.
     */
    public function index(): ViewResponse
    {
        try {
            $total = $this->rebuildSmiliesCacheUseCase->refreshIfStale();
        } catch (SmiliesCacheWriteException) {
            return $this->renderPage(__('Error updating cache'));
        }

        return $this->renderPage(
            successMessage: $total === null ? '' : sprintf(__('The folders have changed, so the list of the smilies is built again. Pictures: %d.'), $total)
        );
    }

    public function rebuild(): ViewResponse
    {
        try {
            $total = $this->rebuildSmiliesCacheUseCase->execute();
        } catch (SmiliesCacheWriteException) {
            return $this->renderPage(__('Error updating cache'));
        }

        $this->session->flash('success_message', sprintf(__('The list of the smilies is built again. Pictures: %d.'), $total));
        redirect(self::URL);
    }

    private function renderPage(string $errorMessage = '', string $successMessage = ''): ViewResponse
    {
        $title = __('Smilies');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/smilies.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'sys_menu'        => ['emoticons' => true],
                'form_action'     => self::URL,
                'folders'         => $this->getFolders->execute(),
                'catalog_url'     => '/help/smilies',
                'error_message'   => $errorMessage,
                'success_message' => $successMessage ?: (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
