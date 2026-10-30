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

    public function index(): ViewResponse
    {
        return $this->renderPage();
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

    private function renderPage(string $errorMessage = ''): ViewResponse
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
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
