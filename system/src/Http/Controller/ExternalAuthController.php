<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\External\AuthenticateViaExternalProviderUseCase;
use Johncms\Auth\External\ExternalAuthException;
use Johncms\Auth\External\ExternalAuthStatus;
use Johncms\Auth\External\ExternalCallbackDTO;
use Johncms\Auth\External\ExternalIdentityDTO;
use Johncms\Auth\External\ExternalIdentityProviderRegistry;
use Johncms\Auth\External\OAuthStateStorage;
use Johncms\Auth\External\RegisterViaExternalProviderUseCase;
use Johncms\Auth\Session\SignInManager;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\SiteBaseUrl;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Johncms\Validator\Rules\EmailAddress;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

/**
 * Signing in through an external service: sending the visitor off, taking them back, and the one
 * screen in between.
 *
 * Core routes rather than routes of the login module, for the reason the sign-in use case is in
 * the core: the panel's login screen offers the same buttons, and it has to work on a site where
 * the public login module is switched off.
 *
 * Nothing here is the provider's business. The provider says who came back; the state, the PKCE
 * verifier, matching against accounts and opening the session all stay here — so the worst a
 * badly written third-party provider can do is break its own button.
 */
final readonly class ExternalAuthController
{
    /** Where the identity waits between the callback and the profile screen. */
    private const PENDING_KEY = 'oauth_pending';

    private const INTENT_SIGN_IN = 'sign_in';

    private const INTENT_LINK = 'link';

    public function __construct(
        private ExternalIdentityProviderRegistry $registry,
        private OAuthStateStorage $stateStorage,
        private AuthenticateViaExternalProviderUseCase $authenticate,
        private RegisterViaExternalProviderUseCase $register,
        private SignInManager $signInManager,
        private CurrentUser $currentUser,
        private Session $session,
        private Environment $environment,
        private ValidatorInterface $validator,
        private SiteBaseUrl $siteUrl,
    ) {
    }

    /**
     * Sends the visitor to the service. A signed-in visitor is linking it to their account; a
     * guest is signing in — decided here rather than by whatever comes back.
     */
    public function start(Request $request, string $provider): ViewResponse
    {
        $service = $this->registry->get($provider);

        if ($service === null || ! $service->isConfigured()) {
            return $this->failure(__('This sign-in service is not available'));
        }

        $signedIn = ! $this->currentUser->isGuest();

        $context = $this->stateStorage->start(
            $provider,
            $this->callbackUrl($request, $provider),
            $signedIn ? self::INTENT_LINK : self::INTENT_SIGN_IN,
            $signedIn ? $this->currentUser->id() : null
        );

        redirect($service->startUrl($context));
    }

    public function callback(Request $request, string $provider): ViewResponse
    {
        $service = $this->registry->get($provider);

        if ($service === null || ! $service->isConfigured()) {
            return $this->failure(__('This sign-in service is not available'));
        }

        try {
            // The redirect_uri comes out of the stored flow, not out of this request: the
            // provider checks it against the one it was given, and rebuilding it here is how the
            // two end up differing.
            $flow = $this->stateStorage->consume($provider, $request->queryParam('state'));

            $identity = $service->handleCallback(
                new ExternalCallbackDTO($request->query->all(), $request->request->all()),
                $flow['context']
            );
        } catch (ExternalAuthException $exception) {
            return $this->failure($exception->getMessage());
        }

        if ($identity->providerUserId === '') {
            return $this->failure(__('The service did not return an account identifier'));
        }

        if ($flow['intent'] === self::INTENT_LINK) {
            return $this->completeLink($provider, $identity, $flow['user_id']);
        }

        return $this->completeSignIn($request, $provider, $identity);
    }

    /**
     * The screen that exists because providers do not hand out logins: VK may withhold the
     * address altogether, and a display name is almost never free as a login here.
     */
    public function profileForm(): ViewResponse
    {
        $pending = $this->pending();

        if ($pending === null) {
            return $this->failure(__('The sign-in attempt has expired, please try again'));
        }

        return $this->profileScreen($pending['identity'], []);
    }

    public function completeProfile(Request $request): ViewResponse
    {
        $pending = $this->pending();

        if ($pending === null) {
            return $this->failure(__('The sign-in attempt has expired, please try again'));
        }

        $identity = $pending['identity'];
        $fields = [
            'name'  => trim($request->body('name', '')),
            'email' => trim($request->body('email', '')) ?: (string) $identity->email,
        ];

        $result = $this->validator->validate(
            $fields,
            [
                'name'  => [
                    new StringLength(min: 2, max: 20),
                    new ModelNotExists(model: User::class, field: 'name'),
                ],
                'email' => [
                    new EmailAddress(),
                    new ModelNotExists(model: User::class, field: 'mail'),
                ],
            ]
        );

        if (! $result->isValid()) {
            return $this->profileScreen($identity, $result->getErrors(), $fields);
        }

        $user = $this->register->execute(
            $pending['provider'],
            $identity,
            $fields['name'],
            $fields['email'],
            $this->environment->getClientInfo(),
            (bool) config('johncms.registration_moderation', false)
        );

        $this->session->remove(self::PENDING_KEY);

        if (! $user->preg) {
            // Registrations wait for an administrator on this site, so there is nothing to sign
            // into yet.
            return $this->result(__('Registration'), __('Your account is awaiting approval'), 'alert-info');
        }

        $this->signInManager->signIn($user->id, true, $request);

        redirect('/');
    }

    private function completeSignIn(Request $request, string $provider, ExternalIdentityDTO $identity): ViewResponse
    {
        try {
            $result = $this->authenticate->execute($provider, $identity);
        } catch (ExternalAuthException $exception) {
            return $this->failure($exception->getMessage());
        }

        return match ($result->status) {
            ExternalAuthStatus::SignedIn => $this->signIn($request, (int) $result->userId),
            ExternalAuthStatus::RegistrationClosed => $this->failure(__('Registration is closed on this site')),
            ExternalAuthStatus::NeedsProfile => $this->askForProfile($provider, $identity),
        };
    }

    private function signIn(Request $request, int $userId): ViewResponse
    {
        $this->signInManager->signIn($userId, true, $request);

        redirect('/');
    }

    private function completeLink(string $provider, ExternalIdentityDTO $identity, ?int $userId): ViewResponse
    {
        // The flow started while they were signed in; if the session ended in between, linking to
        // "whoever is here now" is exactly the mix-up state is meant to prevent.
        if ($userId === null || $this->currentUser->id() !== $userId) {
            return $this->failure(__('The sign-in attempt has expired, please try again'));
        }

        $this->authenticate->link($userId, $provider, $identity);

        redirect('/profile/accounts');
    }

    private function askForProfile(string $provider, ExternalIdentityDTO $identity): ViewResponse
    {
        $this->session->set(
            self::PENDING_KEY,
            [
                'provider'   => $provider,
                'identity'   => [
                    'provider_user_id' => $identity->providerUserId,
                    'email'            => $identity->email,
                    'email_verified'   => $identity->emailVerified,
                    'nickname'         => $identity->nickname,
                    'avatar_url'       => $identity->avatarUrl,
                ],
                'created_at' => time(),
            ]
        );

        redirect('/auth/complete');
    }

    /**
     * @return array{provider: string, identity: ExternalIdentityDTO}|null
     */
    private function pending(): ?array
    {
        /** @var array<string, mixed>|null $pending */
        $pending = $this->session->get(self::PENDING_KEY);

        if (! is_array($pending) || time() - (int) $pending['created_at'] > OAuthStateStorage::TTL) {
            $this->session->remove(self::PENDING_KEY);

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $pending['identity'];

        return [
            'provider' => (string) $pending['provider'],
            'identity' => new ExternalIdentityDTO(
                providerUserId: (string) $data['provider_user_id'],
                email: $data['email'] === null ? null : (string) $data['email'],
                emailVerified: (bool) $data['email_verified'],
                nickname: $data['nickname'] === null ? null : (string) $data['nickname'],
                avatarUrl: $data['avatar_url'] === null ? null : (string) $data['avatar_url'],
            ),
        ];
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @param array<string, string>             $fields
     */
    private function profileScreen(ExternalIdentityDTO $identity, array $errors, array $fields = []): ViewResponse
    {
        $title = __('Finish signing up');

        return new ViewResponse(
            '@theme/pages/external-auth-profile.twig',
            [
                'title'       => $title,
                'page_title'  => $title,
                'form_action' => '/auth/complete',
                'errors'      => $errors,
                'name'        => $fields['name'] ?? (string) $identity->nickname,
                'email'       => $fields['email'] ?? (string) $identity->email,
                'email_known' => $identity->email !== null && $identity->emailVerified,
            ]
        );
    }

    /**
     * The address the provider sends the visitor back to, built from the address the site is
     * actually served on.
     *
     * The site setting comes first, and the request only fills in for it. Behind a proxy that
     * terminates TLS the request looks like plain HTTP unless `http.trusted_proxies` is filled
     * in, so an address taken from it would be `http://…` on an HTTPS site — which providers
     * refuse, VK with a bare "Security Error". The setting is also what /admin/auth/providers
     * shows as the callback to register, so the two cannot disagree.
     */
    private function callbackUrl(Request $request, string $provider): string
    {
        return $this->siteUrl->resolve($request) . '/auth/' . $provider . '/callback';
    }

    private function failure(string $message): ViewResponse
    {
        return $this->result(__('Login'), $message, 'alert-danger');
    }

    private function result(string $title, string $message, string $type): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => $title,
                'page_title'    => $title,
                'type'          => $type,
                'message'       => $message,
                'back_url'      => '/login',
                'back_url_name' => __('Login'),
            ]
        );
    }
}
