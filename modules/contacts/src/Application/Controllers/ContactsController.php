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
use Johncms\System\Http\Environment;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
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
        private Request $request,
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

    public function __invoke(): string
    {
        $pageTitle = __('Contacts');
        $this->navChain->add($pageTitle, self::URL);

        $pageData = $this->settingsProvider->getPageData();
        $consents = $pageData->formEnabled
            ? $this->consentService->getFormConsents(self::CONSENT_CONTEXT)
            : [];

        $formData = $this->form->getFormData();
        $errors = [];

        if ($this->request->getMethod() === 'POST' && $pageData->formEnabled) {
            $consentFields = [];
            foreach ($consents as $consent) {
                $field = 'consent_' . $consent->id;
                $formData[$field] = (string) $this->request->getPost($field, '');
                if ($consent->isRequired) {
                    $consentFields[$field] = ['Identical' => ['token' => '1']];
                }
            }

            $consentMessage = __('You must accept the consent to continue');
            $validator = new Validator(
                $formData,
                $this->form->getValidationRules() + $consentFields,
                [
                    'Identical' => [
                        Identical::NOT_SAME      => $consentMessage,
                        Identical::MISSING_TOKEN => $consentMessage,
                    ],
                ]
            );

            if ($validator->isValid()) {
                $ip = (string) $this->environment->getIp(false);
                $this->submitMessage->execute(
                    new CreateContactMessageDTO(
                        userId:    $this->user->isValid() ? $this->user->id : null,
                        name:      $formData['name'],
                        email:     $formData['email'],
                        message:   $formData['message'],
                        ip:        $ip,
                        userAgent: $this->environment->getUserAgent(),
                    )
                );

                foreach ($consents as $consent) {
                    if (($formData['consent_' . $consent->id] ?? '') === '1') {
                        $this->consentService->logAcceptance(
                            $consent->id,
                            $this->user->isValid() ? $this->user->id : null,
                            $ip,
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
