<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\DTO\UpdateForumSettingsCommand;
use Johncms\Modules\Profile\Application\DTO\UpdateMailSettingsCommand;
use Johncms\Modules\Profile\Application\DTO\UpdateUserSettingsCommand;
use Johncms\Modules\Profile\Application\UseCases\ForumSettingsUseCase;
use Johncms\Modules\Profile\Application\UseCases\MailSettingsUseCase;
use Johncms\Modules\Profile\Application\UseCases\UserSettingsUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class SettingsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private UserSettingsUseCase $userSettingsUseCase,
        private ForumSettingsUseCase $forumSettingsUseCase,
        private MailSettingsUseCase $mailSettingsUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function general(): string
    {
        $config = config('johncms');

        $data = [
            'buttons'         => $this->buttons('general'),
            'form_action'     => '/profile/settings',
            'system_time'     => date('H:i', time() + ($config['timeshift'] + $this->currentUser->set_user->timeshift) * 3600),
            'success_message' => $this->pullFlash(),
        ];

        if (count($config['lng_list']) > 1) {
            $data['user_lng'] = $this->currentUser->set_user->lng ?? $config['lng'];
            $data['lng_list'] = $config['lng_list'];
        }

        return $this->renderPage('profile::settings', __('General setting'), $data);
    }

    public function saveGeneral(): string
    {
        $command = new UpdateUserSettingsCommand(
            timeshift: (int) $this->request->getPost('timeshift', 0, FILTER_VALIDATE_INT),
            directUrl: $this->request->getPost('directUrl') !== null,
            youtube: $this->request->getPost('youtube') !== null,
            fieldHeight: (int) $this->request->getPost('fieldHeight', 3, FILTER_VALIDATE_INT),
            kmess: (int) $this->request->getPost('kmess', 10, FILTER_VALIDATE_INT),
            lng: trim((string) $this->request->getPost('iso', '')),
        );

        $selectedLng = $this->userSettingsUseCase->save($command, $this->currentUser);
        if ($selectedLng !== null) {
            $_SESSION['lng'] = $selectedLng;
        }

        $_SESSION['set_ok'] = 1;
        redirect('/profile/settings');
    }

    public function resetGeneral(): string
    {
        $this->userSettingsUseCase->reset($this->currentUser);
        $_SESSION['reset_ok'] = 1;
        redirect('/profile/settings');
    }

    public function forum(): string
    {
        return $this->renderForumView($this->forumSettingsUseCase->getCurrent($this->currentUser), null);
    }

    public function saveForum(): string
    {
        $command = new UpdateForumSettingsCommand(
            farea: $this->request->getPost('farea') !== null,
            upfp: $this->request->getPost('upfp') !== null,
            preview: $this->request->getPost('preview') !== null,
            postclip: (int) $this->request->getPost('postclip', 1, FILTER_VALIDATE_INT),
        );

        $setForum = $this->forumSettingsUseCase->save($command, $this->currentUser);

        return $this->renderForumView($setForum, __('Settings saved successfully'));
    }

    public function resetForum(): string
    {
        return $this->renderForumView($this->forumSettingsUseCase->reset($this->currentUser), __('Default settings are set'));
    }

    public function mail(): string
    {
        return $this->renderMailView($this->mailSettingsUseCase->getCurrent($this->currentUser), null);
    }

    public function saveMail(): string
    {
        $command = new UpdateMailSettingsCommand(
            access: (int) $this->request->getPost('access', 0, FILTER_VALIDATE_INT),
        );

        $setMail = $this->mailSettingsUseCase->save($command, $this->currentUser);

        return $this->renderMailView($setMail, __('Settings saved successfully'));
    }

    /**
     * @param array<string, mixed> $setForum
     */
    private function renderForumView(array $setForum, ?string $successMessage): string
    {
        return $this->renderPage(
            'profile::forum_settings',
            __('Forum'),
            [
                'buttons'         => $this->buttons('forum'),
                'form_action'     => '/profile/settings/forum',
                'set_forum'       => $setForum,
                'success_message' => $successMessage,
            ]
        );
    }

    /**
     * @param array<string, mixed> $setMail
     */
    private function renderMailView(array $setMail, ?string $successMessage): string
    {
        return $this->renderPage(
            'profile::mail_settings',
            __('Mail'),
            [
                'buttons'         => $this->buttons('mail'),
                'form_action'     => '/profile/settings/mail',
                'set_mail_user'   => $setMail,
                'success_message' => $successMessage,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderPage(string $template, string $title, array $data): string
    {
        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Settings'), '/profile/settings');
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            $template,
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
    }

    /**
     * @return list<array{url: string, name: string, active: bool}>
     */
    private function buttons(string $active): array
    {
        return [
            [
                'url'    => '/profile/settings',
                'name'   => __('General setting'),
                'active' => $active === 'general',
            ],
            [
                'url'    => '/profile/settings/forum',
                'name'   => __('Forum'),
                'active' => $active === 'forum',
            ],
            [
                'url'    => '/profile/settings/mail',
                'name'   => __('Mail'),
                'active' => $active === 'mail',
            ],
        ];
    }

    private function pullFlash(): ?string
    {
        if (isset($_SESSION['set_ok'])) {
            unset($_SESSION['set_ok']);
            return __('Settings saved successfully');
        }

        if (isset($_SESSION['reset_ok'])) {
            unset($_SESSION['reset_ok']);
            return __('Default settings are set');
        }

        return null;
    }
}
