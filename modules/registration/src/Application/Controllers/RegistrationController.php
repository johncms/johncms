<?php

declare(strict_types=1);

namespace Johncms\Modules\Registration\Application\Controllers;

use Illuminate\Support\Str;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Registration\Application\DTO\RegistrationFormDTO;
use Johncms\Modules\Registration\Application\UseCases\RegisterUserUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Laminas\Validator\Hostname;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

final readonly class RegistrationController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private RegisterUserUseCase $registerUser,
    ) {
        $this->controllerContext->initModule('registration');
    }

    public function __invoke(): string
    {
        $config = config('johncms');

        if (! $config['mod_reg'] || $this->currentUser->isValid()) {
            return $this->render->render('registration::registration_closed');
        }

        $this->navChain->add(__('Registration'));

        $fields = [
            'name'     => (string) $this->request->getPost('name', ''),
            'name_lat' => Str::slug((string) $this->request->getPost('name', ''), '_'),
            'password' => (string) $this->request->getPost('password', ''),
            'sex'      => (string) $this->request->getPost('sex', ''),
            'imname'   => (string) $this->request->getPost('imname', ''),
            'about'    => (string) $this->request->getPost('about', ''),
            'captcha'  => $this->request->getPost('captcha'),
            'email'    => (string) $this->request->getPost('email', ''),
        ];

        $errors = [];

        if ($this->request->getMethod() === 'POST') {
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

            $validator = new Validator($fields, $rules);
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

                $newUser = $this->registerUser->execute($dto);

                if ($config['mod_reg'] !== 1 && empty($config['user_email_confirmation'])) {
                    setcookie('cuid', (string) $newUser->id, time() + 3600 * 24 * 365, '/');
                    setcookie('cups', md5($fields['password']), time() + 3600 * 24 * 365, '/');
                }

                return $this->render->render(
                    'registration::registration_result',
                    [
                        'usid'     => $newUser->id,
                        'reg_nick' => $fields['name'],
                        'reg_pass' => $fields['password'],
                    ]
                );
            }

            $errors = $validator->getErrors();
            unset($_SESSION['code']);
        }

        $code = (string) new Code();
        $_SESSION['code'] = $code;

        return $this->render->render(
            'registration::index',
            [
                'errors'  => $errors,
                'fields'  => $fields,
                'captcha' => new Image($code),
            ]
        );
    }
}
