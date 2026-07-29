<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Consent\Application\Services\ConsentService;
use Johncms\Modules\Contacts\Application\DTO\CreateContactMessageDTO;
use Johncms\Modules\Contacts\Application\Forms\ContactForm;
use Johncms\Modules\Contacts\Application\Services\ContactSettingsProvider;
use Johncms\Modules\Contacts\Application\Services\ContactsCaptchaService;
use Johncms\Modules\Contacts\Application\UseCases\SubmitContactMessageUseCase;
use Johncms\NavChain;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Laminas\Validator\Identical;

final readonly class ContactsController
{
    private const URL = '/contacts/';
    private const CONSENT_CONTEXT = 'contacts';

    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Session $session,
        private NavChain $navChain,
        private Environment $environment,
        private User $user,
        private ContactSettingsProvider $settingsProvider,
        private ContactForm $form,
        private ContactsCaptchaService $captchaService,
        private ConsentService $consentService,
        private SubmitContactMessageUseCase $submitMessage,
    ) {
        $this->controllerContext->initModule('contacts');
    }

    public function __invoke(Request $request): string
    {
        $pageTitle = __('Contacts');
        $this->navChain->add($pageTitle, self::URL);

        $pageData = $this->settingsProvider->getPageData();
        $consents = $pageData->formEnabled
            ? $this->consentService->getFormConsents(self::CONSENT_CONTEXT)
            : [];

        $formData = $this->form->getFormData($request);
        $errors = [];

        if ($request->getMethod() === 'POST' && $pageData->formEnabled) {
            $consentFields = [];
            foreach ($consents as $consent) {
                $field = 'consent_' . $consent->id;
                $formData[$field] = $request->body($field, '');
                if ($consent->isRequired) {
                    $consentFields[$field] = ['Identical' => ['token' => '1']];
                }
            }

            $clientInfo = $this->environment->getClientInfo();
            $consentMessage = __('You must accept the consent to continue');
            $validator = new Validator(
                $formData,
                $this->form->getValidationRules($clientInfo) + $consentFields,
                [
                    'Identical' => [
                        Identical::NOT_SAME      => $consentMessage,
                        Identical::MISSING_TOKEN => $consentMessage,
                    ],
                ]
            );

            if ($validator->isValid()) {
                $this->submitMessage->execute(
                    new CreateContactMessageDTO(
                        userId:    $this->user->isValid() ? $this->user->id : null,
                        name:      $formData['name'],
                        email:     $formData['email'],
                        message:   $formData['message'],
                        ip:        $clientInfo->ip,
                        userAgent: $clientInfo->userAgent,
                    )
                );

                foreach ($consents as $consent) {
                    if (($formData['consent_' . $consent->id] ?? '') === '1') {
                        $this->consentService->logAcceptance(
                            $consent->id,
                            $this->user->isValid() ? $this->user->id : null,
                            $clientInfo->ip,
                            $consent->version
                        );
                    }
                }

                $this->captchaService->forget();
                $this->session->flash('message', __('Your message has been sent. We will reply as soon as possible.'));
                redirect(self::URL);
            }

            $errors = $validator->getErrors();
        }

        $this->render->addData(
            [
                'title'      => $pageTitle,
                'page_title' => $pageTitle,
            ]
        );

        return $this->render->render(
            'contacts::index',
            [
                'contacts'       => $pageData,
                'form_action'    => self::URL,
                'form_data'      => $formData,
                'errors'         => $errors,
                'consents'       => $consents,
                'show_captcha'   => ! $this->user->isValid(),
                'captcha'        => $this->user->isValid() ? '' : $this->captchaService->generate(),
                'honeypot_field' => ContactForm::HONEYPOT_FIELD,
                'message'        => $this->session->getFlash('message'),
            ]
        );
    }
}
