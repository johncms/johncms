<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\View\ViewResponse;
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
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Users\UserImages;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditProfileController
{
    public function __construct(
        private NavChain $navChain,
        private GetEditContextUseCase $getEditContextUseCase,
        private UpdateProfileUseCase $updateProfileUseCase,
        private DeleteAvatarUseCase $deleteAvatarUseCase,
        private DeletePhotoUseCase $deletePhotoUseCase,
        private Session $session,
        private CurrentUser $currentUser,
        private UserImages $userImages,
    ) {
    }

    public function form(int $id): ViewResponse
    {
        $context = $this->resolveContext($id);
        if ($context instanceof ViewResponse) {
            return $context;
        }

        $successMessage = $this->session->getFlash('success_message');

        return $this->renderForm($context, $this->formDataFromUser($context->profileUser), [], $successMessage);
    }

    public function save(Request $request, int $id): ViewResponse
    {
        $context = $this->resolveContext($id);
        if ($context instanceof ViewResponse) {
            return $context;
        }

        $command = $this->buildCommand($request);

        try {
            $this->updateProfileUseCase->execute($command, $context->profileUser);
        } catch (EditProfileException $e) {
            return $this->renderForm($context, $command->toFormData(), $e->getErrors(), null);
        }

        $this->session->flash('success_message', __('Data saved'));
        redirect('/profile/' . $context->profileUser->id . '/edit');
    }

    public function deleteAvatar(int $id): ViewResponse
    {
        $context = $this->resolveContext($id);
        if ($context instanceof ViewResponse) {
            return $context;
        }

        $this->deleteAvatarUseCase->execute($context->profileUser->id);
        $this->session->flash('success_message', __('Avatar is successfully removed'));
        redirect('/profile/' . $context->profileUser->id . '/edit');
    }

    public function deletePhoto(int $id): ViewResponse
    {
        $context = $this->resolveContext($id);
        if ($context instanceof ViewResponse) {
            return $context;
        }

        $this->deletePhotoUseCase->execute($context->profileUser->id);
        $this->session->flash('success_message', __('Photo is successfully removed'));
        redirect('/profile/' . $context->profileUser->id . '/edit');
    }

    /**
     * Resolve the edit context or, on failure, a rendered error page (with the proper HTTP status set).
     */
    private function resolveContext(int $id): EditProfileContextDTO|ViewResponse
    {
        try {
            return $this->getEditContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }
    }

    private function buildCommand(Request $request): UpdateProfileCommand
    {
        return new UpdateProfileCommand(
            imname: (string) $request->request->filter('imname', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            live: (string) $request->request->filter('live', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            dayb: htmlspecialchars($request->body('dayb', '')),
            monthb: htmlspecialchars($request->body('monthb', '')),
            yearofbirth: htmlspecialchars($request->body('yearofbirth', '')),
            about: (string) $request->request->filter('about', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mibile: (string) $request->request->filter('mibile', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mail: (string) $request->request->filter('mail', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mailvis: $request->bodyInt('mailvis'),
            skype: (string) $request->request->filter('skype', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            jabber: (string) $request->request->filter('jabber', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            www: (string) $request->request->filter('www', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            status: (string) $request->request->filter('status', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            name: (string) $request->request->filter('name', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            karmaOff: $request->bodyInt('karma_off'),
            sex: (string) $request->request->filter('sex', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            adminNotes: $request->body('admin_notes', ''),
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
            'admin_notes' => $user->admin_notes,
        ];
    }

    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $errors
     */
    private function renderForm(EditProfileContextDTO $context, array $formData, array $errors, ?string $successMessage): ViewResponse
    {
        $title = __('Edit Profile');

        $this->navChain->add($context->isSelf ? __('My Profile') : __('Profile'), '/profile/' . $context->profileUser->id);
        $this->navChain->add($title);

        $userArray = $context->profileUser->toArray();
        $userArray['photo'] = $context->profileUser->photo;

        $hasAvatar = $this->userImages->hasAvatar($context->profileUser->id);

        return new ViewResponse(
            '@profile/public/edit.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'errors'          => $errors,
                'success_message' => $successMessage,
                'back_url'        => '/profile/' . $context->profileUser->id,
                'form_action'     => '/profile/' . $context->profileUser->id . '/edit',
                'has_avatar'      => $hasAvatar,
                'field_height'    => $this->currentUser->user()->config->fieldHeight,
                'user'            => $userArray,
                'form_data'       => $formData,
                'can_edit_admin_fields' => $context->canEditAdminFields,
                'can_reset_settings'    => $context->canResetSettings,
            ]
        );
    }

    private function renderError(string $message, int $status = 200): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => __('Edit Profile'),
                'type'    => 'alert-danger',
                'message' => $message,
            ],
            $status
        );
    }
}
