<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\DTO\EditProfileContextDTO;
use Johncms\Modules\Profile\Application\DTO\UpdateProfileCommand;
use Johncms\Modules\Profile\Application\Exceptions\EditProfileException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\DeleteAvatarUseCase;
use Johncms\Modules\Profile\Application\UseCases\DeletePhotoUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetEditContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\UpdateProfileUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class EditProfileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private GetEditContextUseCase $getEditContextUseCase,
        private UpdateProfileUseCase $updateProfileUseCase,
        private DeleteAvatarUseCase $deleteAvatarUseCase,
        private DeletePhotoUseCase $deletePhotoUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function form(int $id): string
    {
        $context = $this->resolveContext($id);
        if (is_string($context)) {
            return $context;
        }

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        return $this->renderForm($context, $this->formDataFromUser($context->profileUser), [], $successMessage);
    }

    public function save(int $id): string
    {
        $context = $this->resolveContext($id);
        if (is_string($context)) {
            return $context;
        }

        $command = $this->buildCommand();

        try {
            $this->updateProfileUseCase->execute($command, $context->profileUser);
        } catch (EditProfileException $e) {
            return $this->renderForm($context, $command->toFormData(), $e->getErrors(), null);
        }

        $_SESSION['success_message'] = __('Data saved');
        redirect('/profile/' . $context->profileUser->id . '/edit');
    }

    public function deleteAvatar(int $id): string
    {
        $context = $this->resolveContext($id);
        if (is_string($context)) {
            return $context;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $this->deleteAvatarUseCase->execute($context->profileUser->id);
        $_SESSION['success_message'] = __('Avatar is successfully removed');
        redirect('/profile/' . $context->profileUser->id . '/edit');
    }

    public function deletePhoto(int $id): string
    {
        $context = $this->resolveContext($id);
        if (is_string($context)) {
            return $context;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $this->deletePhotoUseCase->execute($context->profileUser->id);
        $_SESSION['success_message'] = __('Photo is successfully removed');
        redirect('/profile/' . $context->profileUser->id . '/edit');
    }

    /**
     * Resolve the edit context or, on failure, a rendered error page (with the proper HTTP status set).
     *
     * @return EditProfileContextDTO|string
     */
    private function resolveContext(int $id): EditProfileContextDTO|string
    {
        try {
            return $this->getEditContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($e->getMessage());
        }
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function buildCommand(): UpdateProfileCommand
    {
        return new UpdateProfileCommand(
            imname: (string) $this->request->getPost('imname', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            live: (string) $this->request->getPost('live', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            dayb: htmlspecialchars((string) $this->request->getPost('dayb', '')),
            monthb: htmlspecialchars((string) $this->request->getPost('monthb', '')),
            yearofbirth: htmlspecialchars((string) $this->request->getPost('yearofbirth', '')),
            about: (string) $this->request->getPost('about', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mibile: (string) $this->request->getPost('mibile', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mail: (string) $this->request->getPost('mail', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mailvis: (int) $this->request->getPost('mailvis', 0, FILTER_VALIDATE_INT),
            skype: (string) $this->request->getPost('skype', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            jabber: (string) $this->request->getPost('jabber', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            www: (string) $this->request->getPost('www', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            status: (string) $this->request->getPost('status', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            csrfToken: (string) $this->request->getPost('csrf_token', ''),
            name: (string) $this->request->getPost('name', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            karmaOff: (int) $this->request->getPost('karma_off', 0, FILTER_VALIDATE_INT),
            sex: (string) $this->request->getPost('sex', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            rights: (int) $this->request->getPost('rights', 0, FILTER_VALIDATE_INT),
            adminNotes: (string) $this->request->getPost('admin_notes', ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formDataFromUser(User $user): array
    {
        return [
            'imname'      => $user->imname,
            'live'        => $user->live,
            'dayb'        => $user->dayb,
            'monthb'      => $user->monthb,
            'yearofbirth' => $user->yearofbirth,
            'about'       => $user->about,
            'mibile'      => $user->mibile,
            'mail'        => $user->mail,
            'mailvis'     => $user->mailvis,
            'skype'       => $user->skype,
            'jabber'      => $user->jabber,
            'www'         => $user->www,
            'status'      => $user->status,
            'name'        => $user->name,
            'karma_off'   => $user->karma_off,
            'sex'         => $user->sex,
            'rights'      => $user->rights,
            'admin_notes' => $user->admin_notes,
        ];
    }

    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $errors
     */
    private function renderForm(EditProfileContextDTO $context, array $formData, array $errors, ?string $successMessage): string
    {
        $title = __('Edit Profile');

        $this->navChain->add($context->isSelf ? __('My Profile') : __('Profile'), '/profile/' . $context->profileUser->id);
        $this->navChain->add($title);

        $userArray = $context->profileUser->toArray();
        $userArray['photo'] = $context->profileUser->photo;

        $avatarPath = UPLOAD_PATH . 'users/avatar/' . $context->profileUser->id . '.png';
        $hasAvatar = is_file($avatarPath);
        if ($hasAvatar) {
            $userArray['avatar_file'] = $avatarPath;
        }

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::edit',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'errors'          => $errors,
                    'success_message' => $successMessage,
                    'back_url'        => '/profile/' . $context->profileUser->id,
                    'form_action'     => '/profile/' . $context->profileUser->id . '/edit',
                    'has_avatar'      => $hasAvatar,
                    'user'            => $userArray,
                    'form_data'       => $formData,
                ],
            ]
        );
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Edit Profile'),
                'type'    => 'alert-danger',
                'message' => $message,
            ]
        );
    }
}
