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
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class SetAvatarController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(string $id, int $avatar): string
    {
        if (! $this->currentUser->isValid()) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'         => __('Access denied'),
                'type'          => 'alert-danger',
                'message'       => __('You are not logged in'),
                'back_url'      => '/help/avatars/' . $id . '/',
                'back_url_name' => __('Back'),
            ]);
        }

        $sourcePath = ASSETS_PATH . 'avatars/' . $id . '/' . $avatar . '.png';

        if (! is_file($sourcePath)) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('Wrong data'),
                'type'          => 'alert-danger',
                'message'       => __('File does not exist'),
                'back_url'      => '/help/avatars/' . $id . '/',
                'back_url_name' => __('Back'),
            ]);
        }

        $pageTitle = __('Set to Profile');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add(__('Avatars'), '/help/avatars/');
        $this->navChain->add($pageTitle);

        $this->render->addData([
            'title'      => $pageTitle,
            'page_title' => $pageTitle,
        ]);

        if ($this->request->getMethod() === 'POST') {
            $targetPath = UPLOAD_PATH . 'users/avatar/' . $this->currentUser->id . '.png';
            if (@copy($sourcePath, $targetPath)) {
                return $this->render->render('system::pages/result', [
                    'title'         => $pageTitle,
                    'type'          => 'alert-success',
                    'message'       => __('Avatar has been successfully applied'),
                    'back_url'      => '/profile/' . $this->currentUser->id . '/edit',
                    'back_url_name' => __('Continue'),
                ]);
            }

            return $this->render->render('system::pages/result', [
                'title'         => $pageTitle,
                'type'          => 'alert-danger',
                'message'       => __('An error occurred'),
                'back_url'      => '/help/avatars/',
                'back_url_name' => __('Back'),
            ]);
        }

        return $this->render->render('help::confirm', [
            'data' => [
                'form_action'     => '/help/avatars/' . $id . '/set/' . $avatar . '/',
                'message'         => __('Are you sure you want to set yourself this avatar?'),
                'img'             => '/assets/avatars/' . $id . '/' . $avatar . '.png',
                'back_url'        => '/help/avatars/' . $id . '/',
                'submit_btn_name' => __('Save'),
            ],
        ]);
    }
}
