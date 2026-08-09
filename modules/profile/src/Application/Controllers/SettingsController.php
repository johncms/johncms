<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Modules\Profile\Application\DTO\UpdateForumSettingsCommand;
use Johncms\Modules\Profile\Application\DTO\UpdateMailSettingsCommand;
use Johncms\Modules\Profile\Application\DTO\UpdateUserSettingsCommand;
use Johncms\Modules\Profile\Application\UseCases\ForumSettingsUseCase;
use Johncms\Modules\Profile\Application\UseCases\MailSettingsUseCase;
use Johncms\Modules\Profile\Application\UseCases\UserSettingsUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;

final readonly class SettingsController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private UserSettingsUseCase $userSettingsUseCase,
        private ForumSettingsUseCase $forumSettingsUseCase,
        private MailSettingsUseCase $mailSettingsUseCase,
        private Session $session,
    ) {
    }

    public function general(): ViewResponse
    {
        $config = config('johncms');

        $data = [
            'buttons'     => $this->buttons('general'),
            'user_config' => $this->currentUser->config,
            'site_lng'    => $config['lng'],
            'lng_list'        => [],
            'user_lng'        => '',
            'form_action'     => '/profile/settings',
            'system_time'     => date('H:i', time() + ($config['timeshift'] + $this->currentUser->set_user->timeshift) * 3600),
            'success_message' => $this->pullFlash(),
        ];

        if (count($config['lng_list']) > 1) {
            $data['user_lng'] = $this->currentUser->set_user->lng ?? $config['lng'];
            $data['lng_list'] = $config['lng_list'];
        }

        return $this->renderPage('@profile/public/settings.twig', __('General setting'), $data);
    }

    public function saveGeneral(Request $request): ViewResponse
    {
        $command = new UpdateUserSettingsCommand(
            timeshift: $request->bodyInt('timeshift'),
            directUrl: $request->hasBody('directUrl'),
            youtube: $request->hasBody('youtube'),
            fieldHeight: $request->bodyInt('fieldHeight', 3),
            kmess: $request->bodyInt('kmess', 10),
            lng: trim($request->body('iso', '')),
        );

        $selectedLng = $this->userSettingsUseCase->save($command, $this->currentUser);
        if ($selectedLng !== null) {
            $this->session->set('lng', $selectedLng);
        }

        $this->session->flash('set_ok', true);
        redirect('/profile/settings');
    }

    public function resetGeneral(): ViewResponse
    {
        $this->userSettingsUseCase->reset($this->currentUser);
        $this->session->flash('reset_ok', true);
        redirect('/profile/settings');
    }

    public function forum(): ViewResponse
    {
        return $this->renderForumView($this->forumSettingsUseCase->getCurrent($this->currentUser), null);
    }

    public function saveForum(Request $request): ViewResponse
    {
        $command = new UpdateForumSettingsCommand(
            farea: $request->hasBody('farea'),
            upfp: $request->hasBody('upfp'),
            preview: $request->hasBody('preview'),
            postclip: $request->bodyInt('postclip', 1),
        );

        $setForum = $this->forumSettingsUseCase->save($command, $this->currentUser);

        return $this->renderForumView($setForum, __('Settings saved successfully'));
    }

    public function resetForum(): ViewResponse
    {
        return $this->renderForumView($this->forumSettingsUseCase->reset($this->currentUser), __('Default settings are set'));
    }

    public function mail(): ViewResponse
    {
        return $this->renderMailView($this->mailSettingsUseCase->getCurrent($this->currentUser), null);
    }

    public function saveMail(Request $request): ViewResponse
    {
        $command = new UpdateMailSettingsCommand(
            access: $request->bodyInt('access'),
        );

        $setMail = $this->mailSettingsUseCase->save($command, $this->currentUser);

        return $this->renderMailView($setMail, __('Settings saved successfully'));
    }

    /**
     * @param array<string, mixed> $setForum
     */
    private function renderForumView(array $setForum, ?string $successMessage): ViewResponse
    {
        return $this->renderPage(
            '@profile/public/forum-settings.twig',
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
    private function renderMailView(array $setMail, ?string $successMessage): ViewResponse
    {
        return $this->renderPage(
            '@profile/public/mail-settings.twig',
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
    private function renderPage(string $template, string $title, array $data): ViewResponse
    {
        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Settings'), '/profile/settings');
        $this->navChain->add($title);

        return new ViewResponse(
            $template,
            $data + [
                'title'      => $title,
                'page_title' => $title,
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
        if ($this->session->getFlash('set_ok')) {
            return __('Settings saved successfully');
        }

        if ($this->session->getFlash('reset_ok')) {
            return __('Default settings are set');
        }

        return null;
    }
}
