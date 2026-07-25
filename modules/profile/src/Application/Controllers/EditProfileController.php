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
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

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

    public function form(int $id): Response
    {
        $context = $this->resolveContext($id);
        if ($context instanceof Response) {
            return $context;
        }

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        return $this->renderForm($context, $this->formDataFromUser($context->profileUser), [], $successMessage);
    }

    public function save(int $id): Response
    {
        $context = $this->resolveContext($id);
        if ($context instanceof Response) {
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

    public function deleteAvatar(int $id): Response
    {
        $context = $this->resolveContext($id);
        if ($context instanceof Response) {
            return $context;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $this->deleteAvatarUseCase->execute($context->profileUser->id);
        $_SESSION['success_message'] = __('Avatar is successfully removed');
        redirect('/profile/' . $context->profileUser->id . '/edit');
    }

    public function deletePhoto(int $id): Response
    {
        $context = $this->resolveContext($id);
        if ($context instanceof Response) {
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
     */
    private function resolveContext(int $id): EditProfileContextDTO|Response
    {
        try {
            return $this->getEditContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function buildCommand(): UpdateProfileCommand
    {
        return new UpdateProfileCommand(
            imname: (string) $this->request->request->filter('imname', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            live: (string) $this->request->request->filter('live', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            dayb: htmlspecialchars($this->request->body('dayb', '')),
            monthb: htmlspecialchars($this->request->body('monthb', '')),
            yearofbirth: htmlspecialchars($this->request->body('yearofbirth', '')),
            about: (string) $this->request->request->filter('about', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mibile: (string) $this->request->request->filter('mibile', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mail: (string) $this->request->request->filter('mail', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            mailvis: $this->request->bodyInt('mailvis'),
            skype: (string) $this->request->request->filter('skype', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            jabber: (string) $this->request->request->filter('jabber', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            www: (string) $this->request->request->filter('www', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            status: (string) $this->request->request->filter('status', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            csrfToken: $this->request->body('csrf_token', ''),
            name: (string) $this->request->request->filter('name', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            karmaOff: $this->request->bodyInt('karma_off'),
            sex: (string) $this->request->request->filter('sex', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            rights: $this->request->bodyInt('rights'),
            adminNotes: $this->request->body('admin_notes', ''),
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
    private function renderForm(EditProfileContextDTO $context, array $formData, array $errors, ?string $successMessage): Response
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

        return new Response(
            $this->render->render(
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
            )
        );
    }

    private function renderError(string $message, int $status = 200): Response
    {
        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('Edit Profile'),
                    'type'    => 'alert-danger',
                    'message' => $message,
                ]
            ),
            $status
        );
    }
}
