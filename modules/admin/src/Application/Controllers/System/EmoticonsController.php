<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\UseCases\RebuildSmiliesCacheUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\SmiliesCacheWriteException;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Validator\Validator;

final readonly class EmoticonsController
{
    private const URL = '/admin/emoticons';

    public function __construct(
        private NavChain $navChain,
        private RebuildSmiliesCacheUseCase $rebuildSmiliesCacheUseCase,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        return $this->renderPage();
    }

    public function rebuild(Request $request): ViewResponse
    {
        if (! $this->isCsrfValid($request)) {
            return $this->renderPage(__('Wrong data'));
        }

        try {
            $total = $this->rebuildSmiliesCacheUseCase->execute();
        } catch (SmiliesCacheWriteException) {
            return $this->renderPage(__('Error updating cache'));
        }

        $this->session->flash('success_message', __('Smilie cache updated successfully') . ': ' . $total);
        redirect(self::URL);
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
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
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
