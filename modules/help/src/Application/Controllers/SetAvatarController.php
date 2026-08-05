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
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetAvatarController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(Request $request, string $id, int $avatar): ViewResponse
    {
        if (! $this->currentUser->isValid()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Access denied'),
                    'type'          => 'alert-danger',
                    'message'       => __('You are not logged in'),
                    'back_url'      => '/help/avatars/' . $id . '/',
                    'back_url_name' => __('Back'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $sourcePath = ASSETS_PATH . 'avatars/' . $id . '/' . $avatar . '.png';

        if (! is_file($sourcePath)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('File does not exist'),
                    'back_url'      => '/help/avatars/' . $id . '/',
                    'back_url_name' => __('Back'),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $pageTitle = __('Set to Profile');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add(__('Avatars'), '/help/avatars/');
        $this->navChain->add($pageTitle);

        if ($request->getMethod() === 'POST') {
            $targetPath = UPLOAD_PATH . 'users/avatar/' . $this->currentUser->id . '.png';
            if (@copy($sourcePath, $targetPath)) {
                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => $pageTitle,
                        'type'          => 'alert-success',
                        'message'       => __('Avatar has been successfully applied'),
                        'back_url'      => '/profile/' . $this->currentUser->id . '/edit',
                        'back_url_name' => __('Continue'),
                    ]
                );
            }

            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => $pageTitle,
                    'type'          => 'alert-danger',
                    'message'       => __('An error occurred'),
                    'back_url'      => '/help/avatars/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        return new ViewResponse(
            '@help/public/confirm.twig',
            [
                'title'           => $pageTitle,
                'page_title'      => $pageTitle,
                'form_action'     => '/help/avatars/' . $id . '/set/' . $avatar . '/',
                'message'         => __('Are you sure you want to set yourself this avatar?'),
                'img'             => '/assets/avatars/' . $id . '/' . $avatar . '.png',
                'back_url'        => '/help/avatars/' . $id . '/',
                'submit_btn_name' => __('Save'),
            ]
        );
    }
}
