<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers;

use Johncms\Auth\CurrentUser;
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
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Rules\Identical;
use Johncms\Validator\ValidatorInterface;

final readonly class ContactsController
{
    private const URL = '/contacts/';
    private const CONSENT_CONTEXT = 'contacts';

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private Environment $environment,
        private CurrentUser $currentUser,
        private ContactSettingsProvider $settingsProvider,
        private ContactForm $form,
        private ContactsCaptchaService $captchaService,
        private ConsentService $consentService,
        private SubmitContactMessageUseCase $submitMessage,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
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
                    // The message belongs to this rule alone. The previous API took an override
                    // for the whole form, so the same text also replaced the message of the
                    // honeypot field, which carries an Identical of its own.
                    $consentFields[$field] = [
                        new Identical(token: '1', message: __('You must accept the consent to continue')),
                    ];
                }
            }

            $clientInfo = $this->environment->getClientInfo();
            $result = $this->validator->validate(
                $formData,
                $this->form->getValidationRules($clientInfo) + $consentFields
            );

            if ($result->isValid()) {
                $this->submitMessage->execute(
                    new CreateContactMessageDTO(
                        userId:    $this->currentUser->isValid() ? $this->currentUser->id() : null,
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
                            $this->currentUser->isValid() ? $this->currentUser->id() : null,
                            $clientInfo->ip,
                            $consent->version
                        );
                    }
                }

                $this->captchaService->forget();
                $this->session->flash('message', __('Your message has been sent. We will reply as soon as possible.'));
                redirect(self::URL);
            }

            $errors = $result->getErrors();
        }

        return new ViewResponse(
            '@contacts/public/index.twig',
            [
                'title'          => $pageTitle,
                'page_title'     => $pageTitle,
                'contacts'       => $pageData,
                'form_action'    => self::URL,
                'form_data'      => $formData,
                'errors'         => $errors,
                'consents'       => $consents,
                'show_captcha'   => ! $this->currentUser->isValid(),
                'captcha'        => $this->currentUser->isValid() ? '' : $this->captchaService->generate(),
                'honeypot_field' => ContactForm::HONEYPOT_FIELD,
                'message'        => $this->session->getFlash('message'),
            ]
        );
    }
}
