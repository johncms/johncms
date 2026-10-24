<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Access\UserEditAccess;
use Johncms\Modules\Admin\Application\DTO\UpdateUserCommand;
use Johncms\Modules\Admin\Application\Exceptions\UserEditException;
use Johncms\Modules\Admin\Application\Exceptions\UserEditForbiddenException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Services\UserCardMapper;
use Johncms\Modules\Admin\Application\UseCases\ChangeUserPasswordUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetUserEditUseCase;
use Johncms\Modules\Admin\Application\UseCases\ResetUserSettingsUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateUserUseCase;
use Johncms\NavChain;
use Johncms\Users\User;

/**
 * Changing an account from the panel: its profile, its password, its settings. The pages stand
 * beside the page of the account, under its name and its tabs.
 */
final readonly class UserEditController
{
    public function __construct(
        private NavChain $navChain,
        private GetUserEditUseCase $getUser,
        private UpdateUserUseCase $updateUser,
        private ChangeUserPasswordUseCase $changePassword,
        private ResetUserSettingsUseCase $resetSettings,
        private UserEditAccess $access,
        private UserCardMapper $card,
        private Session $session,
    ) {
    }

    public function form(int $id): ViewResponse
    {
        try {
            $user = $this->getUser->forProfile($id);
        } catch (UserNotFoundException | UserEditForbiddenException $e) {
            return $this->refused($e);
        }

        return $this->renderForm($user, $this->formOf($user), []);
    }

    public function save(Request $request, int $id): ViewResponse
    {
        try {
            $user = $this->getUser->forProfile($id);
        } catch (UserNotFoundException | UserEditForbiddenException $e) {
            return $this->refused($e);
        }

        $command = $this->commandOf($request);

        try {
            $this->updateUser->execute($user, $command);
        } catch (UserEditException $e) {
            return $this->renderForm($user, $this->formOfCommand($command), $e->errors);
        }

        $this->session->flash('success_message', __('The profile is saved'));
        redirect('/admin/users/' . $user->id);
    }

    public function passwordForm(int $id): ViewResponse
    {
        try {
            $user = $this->getUser->forPassword($id);
        } catch (UserNotFoundException | UserEditForbiddenException $e) {
            return $this->refused($e);
        }

        return $this->renderPassword($user, []);
    }

    public function savePassword(Request $request, int $id): ViewResponse
    {
        try {
            $user = $this->getUser->forPassword($id);
        } catch (UserNotFoundException | UserEditForbiddenException $e) {
            return $this->refused($e);
        }

        try {
            $this->changePassword->execute(
                $user,
                $request->body('password'),
                $request->body('confirmation'),
                $request->hasBody('sign_out'),
            );
        } catch (UserEditException $e) {
            return $this->renderPassword($user, $e->errors);
        }

        $this->session->flash('success_message', __('The password is changed'));
        redirect('/admin/users/' . $user->id);
    }

    public function resetConfirm(Request $request, int $id): ViewResponse
    {
        try {
            $user = $this->getUser->forSettingsReset($id);
        } catch (UserNotFoundException | UserEditForbiddenException $e) {
            return $this->refused($e, $request->isXmlHttpRequest());
        }

        $dialog = [
            'title'    => __('Reset settings'),
            'message'  => __('The settings of the account and of its forum go back to the defaults.'),
            'user'     => ['id' => $user->id, 'name' => $user->name],
            'action'   => '/admin/users/' . $user->id . '/reset-settings',
            'fields'   => [],
            'confirm'  => __('Reset'),
            'back_url' => '/admin/users/' . $user->id,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/pages/confirm-dialog.twig', $dialog);
        }

        $this->navChain->add($user->name, '/admin/users/' . $user->id);
        $this->navChain->add($dialog['title']);

        return new ViewResponse('@admin/pages/confirm.twig', ['title' => $dialog['title'], 'dialog' => $dialog]);
    }

    public function reset(int $id): ViewResponse
    {
        try {
            $user = $this->getUser->forSettingsReset($id);
        } catch (UserNotFoundException | UserEditForbiddenException $e) {
            return $this->refused($e);
        }

        $this->resetSettings->execute($user);

        $this->session->flash('success_message', __('The settings are reset'));
        redirect('/admin/users/' . $user->id);
    }

    /**
     * @param array<string, mixed>        $form
     * @param array<string, list<string>> $errors
     */
    private function renderForm(User $user, array $form, array $errors): ViewResponse
    {
        return $this->page($user, 'edit', __('Edit'), '@admin/user/edit.twig', [
            'form'         => $form,
            'errors'       => $errors,
            'staff_fields' => $this->access->mayEditStaffFields(),
            'notes_field'  => $this->access->mayEditStaffFields() && $this->access->mayReadNotes(),
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderPassword(User $user, array $errors): ViewResponse
    {
        return $this->page($user, 'password', __('Password'), '@admin/user/password.twig', [
            'errors' => $errors,
            'self'   => $this->access->isSelf($user),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function page(User $user, string $tab, string $title, string $template, array $data): ViewResponse
    {
        $this->navChain->add(__('Users'), '/admin/users');
        $this->navChain->add($user->name, '/admin/users/' . $user->id);
        $this->navChain->add($title);

        return new ViewResponse($template, $data + [
            'title'           => $user->name . ' — ' . $title,
            'page_title'      => $user->name,
            'usr_menu'        => ['userlist' => true],
            'user'            => $this->card->head($user),
            'tabs'            => $this->card->tabs($user, $tab),
            'success_message' => '',
        ]);
    }

    private function refused(UserNotFoundException | UserEditForbiddenException $e, bool $dialog = false): ViewResponse
    {
        $data = [
            'title'    => __('Users'),
            'type'     => 'alert-danger',
            'message'  => $e instanceof UserNotFoundException ? __('User does not exists') : __('You cannot change this account'),
            'back_url' => '/admin/users',
        ];

        if ($dialog) {
            return new ViewResponse('@admin/pages/result-dialog.twig', $data);
        }

        return new ViewResponse('@admin/pages/result.twig', $data + ['page_title' => __('Users')], $e instanceof UserNotFoundException ? 404 : 403);
    }

    private function commandOf(Request $request): UpdateUserCommand
    {
        $staff = $this->access->mayEditStaffFields();

        return new UpdateUserCommand(
            mail: $request->body('mail'),
            mailVisible: $request->hasBody('mailvis'),
            fullName: $request->body('imname'),
            city: $request->body('live'),
            birthDay: $request->bodyInt('dayb'),
            birthMonth: $request->bodyInt('monthb'),
            birthYear: $request->bodyInt('yearofbirth'),
            about: $request->body('about'),
            phone: $request->body('mibile'),
            skype: $request->body('skype'),
            jabber: $request->body('jabber'),
            site: $request->body('www'),
            status: $request->body('status'),
            name: $staff ? $request->body('name') : null,
            sex: $staff ? $request->body('sex') : null,
            karmaOff: $staff ? $request->hasBody('karma_off') : null,
            notes: $staff && $this->access->mayReadNotes() ? $request->body('admin_notes') : null,
        );
    }

    /**
     * The values the form opens with. What the profile of the site saved escaped is shown as the
     * text it stands for, and saved back as typed.
     *
     * @return array<string, mixed>
     */
    private function formOf(User $user): array
    {
        return [
            'name'        => $user->name,
            'mail'        => $this->card->text($user->mail),
            'mailvis'     => (bool) $user->mailvis,
            'sex'         => (string) $user->sex,
            'imname'      => $this->card->text($user->imname),
            'live'        => $this->card->text($user->live),
            'dayb'        => (int) $user->dayb,
            'monthb'      => (int) $user->monthb,
            'yearofbirth' => (int) $user->yearofbirth,
            'about'       => $this->card->text($user->about),
            'mibile'      => $this->card->text($user->mibile),
            'skype'       => $this->card->text($user->skype),
            'jabber'      => $this->card->text($user->jabber),
            'www'         => $this->card->text($user->www),
            'status'      => $this->card->text($user->status),
            'karma_off'   => (bool) $user->karma_off,
            'admin_notes' => (string) $user->admin_notes,
        ];
    }

    /**
     * The values the form opens with again after it was not accepted: what was sent.
     *
     * @return array<string, mixed>
     */
    private function formOfCommand(UpdateUserCommand $command): array
    {
        return [
            'name'        => (string) $command->name,
            'mail'        => $command->mail,
            'mailvis'     => $command->mailVisible,
            'sex'         => (string) $command->sex,
            'imname'      => $command->fullName,
            'live'        => $command->city,
            'dayb'        => $command->birthDay,
            'monthb'      => $command->birthMonth,
            'yearofbirth' => $command->birthYear,
            'about'       => $command->about,
            'mibile'      => $command->phone,
            'skype'       => $command->skype,
            'jabber'      => $command->jabber,
            'www'         => $command->site,
            'status'      => $command->status,
            'karma_off'   => (bool) $command->karmaOff,
            'admin_notes' => (string) $command->notes,
        ];
    }
}
