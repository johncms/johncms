<?php

declare(strict_types=1);

namespace Johncms\Modules\Registration\Application\Controllers;

use Illuminate\Support\Str;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Consent\Application\Services\ConsentService;
use Johncms\Modules\Registration\Application\DTO\RegistrationFormDTO;
use Johncms\Modules\Registration\Application\UseCases\RegisterUserUseCase;
use Johncms\NavChain;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Laminas\Validator\Hostname;
use Laminas\Validator\Identical;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;
use Symfony\Component\HttpFoundation\Cookie;

final readonly class RegistrationController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Session $session,
        private NavChain $navChain,
        private User $currentUser,
        private RegisterUserUseCase $registerUser,
        private ConsentService $consentService,
        private Environment $env,
    ) {
        $this->controllerContext->initModule('registration');
    }

    public function __invoke(Request $request): ViewResponse
    {
        $config = config('johncms');

        if (! $config['mod_reg'] || $this->currentUser->isValid()) {
            return new ViewResponse(
                '@registration/public/registration-closed.twig',
                [
                    'title'      => __('Registration'),
                    'page_title' => __('Registration'),
                ]
            );
        }

        $this->navChain->add(__('Registration'));

        $consents = $this->consentService->getFormConsents('register');

        $fields = [
            'name'     => $request->body('name', ''),
            'name_lat' => Str::slug($request->body('name', ''), '_'),
            'password' => $request->body('password', ''),
            'sex'      => $request->body('sex', ''),
            'imname'   => $request->body('imname', ''),
            'about'    => $request->body('about', ''),
            'captcha'  => $request->body('captcha'),
            'email'    => $request->body('email', ''),
        ];

        foreach ($consents as $consent) {
            $fields['consent_' . $consent->id] = $request->body('consent_' . $consent->id);
        }

        $errors = [];

        if ($request->getMethod() === 'POST') {
            $rules = [
                'name'     => [
                    'NotEmpty',
                    'StringLength'   => ['min' => 2, 'max' => 20],
                    'ModelNotExists' => ['model' => User::class, 'field' => 'name'],
                ],
                'name_lat' => [
                    'ModelNotExists' => ['model' => User::class, 'field' => 'name_lat'],
                ],
                'password' => [
                    'NotEmpty',
                    'StringLength' => ['min' => 6],
                ],
                'sex'      => [
                    'InArray' => ['haystack' => ['m', 'zh']],
                ],
                'captcha'  => ['Captcha'],
            ];

            if (! empty($config['user_email_required']) || ! empty($config['user_email_confirmation'])) {
                $rules['email'] = [
                    'EmailAddress'   => [
                        'allow'          => Hostname::ALLOW_DNS,
                        'useMxCheck'     => true,
                        'useDeepMxCheck' => true,
                    ],
                    'ModelNotExists' => ['model' => User::class, 'field' => 'mail'],
                ];
            }

            foreach ($consents as $consent) {
                if ($consent->isRequired) {
                    $rules['consent_' . $consent->id] = ['Identical' => ['token' => '1']];
                }
            }

            $consentMessage = __('You must accept the consent to continue');
            $messages = [
                'Identical' => [
                    Identical::NOT_SAME      => $consentMessage,
                    Identical::MISSING_TOKEN => $consentMessage,
                ],
            ];

            $validator = new Validator($fields, $rules, $messages);
            if ($validator->isValid()) {
                $dto = new RegistrationFormDTO(
                    name: $fields['name'],
                    nameLat: $fields['name_lat'],
                    password: $fields['password'],
                    sex: $fields['sex'],
                    imname: $fields['imname'],
                    about: $fields['about'],
                    email: $fields['email'],
                );

                $clientInfo = $this->env->getClientInfo();
                $newUser = $this->registerUser->execute($dto, $clientInfo);

                $ip = $clientInfo->ip;
                foreach ($consents as $consent) {
                    if ($fields['consent_' . $consent->id] === '1') {
                        $this->consentService->logAcceptance($consent->id, $newUser->id, $ip, $consent->version);
                    }
                }

                $cookies = [];
                if ($config['mod_reg'] !== 1 && empty($config['user_email_confirmation'])) {
                    $expire = time() + 3600 * 24 * 365;
                    $cookies[] = Cookie::create('cuid', (string) $newUser->id, $expire, '/', null, false, false, false, null);
                    $cookies[] = Cookie::create('cups', md5($fields['password']), $expire, '/', null, false, false, false, null);
                }

                return new ViewResponse(
                    '@registration/public/registration-result.twig',
                    [
                        'title'          => __('Registration'),
                        'page_title'     => __('Registration'),
                        'usid'           => $newUser->id,
                        'reg_nick'       => $fields['name'],
                        'reg_pass'       => $fields['password'],
                        'needs_email'    => ! empty($config['user_email_confirmation']),
                        'needs_approval' => $config['mod_reg'] === 1,
                    ],
                    cookies: $cookies
                );
            }

            $errors = $validator->getErrors();
            $this->session->remove('code');
        }

        $code = (string) new Code();
        $this->session->set('code', $code);

        return new ViewResponse(
            '@registration/public/index.twig',
            [
                'title'          => __('Registration'),
                'page_title'     => __('Registration'),
                'errors'         => $errors,
                'fields'         => $fields,
                'captcha'        => (string) new Image($code),
                'consents'       => $consents,
                'needs_approval' => $config['mod_reg'] === 1,
                'email_required' => ! empty($config['user_email_required']) || ! empty($config['user_email_confirmation']),
            ]
        );
    }
}
