<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Access;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\SiteBaseUrl;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\UseCases\GetExternalProvidersUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateExternalProvidersUseCase;
use Johncms\NavChain;

/**
 * The application keys of the sign-in services.
 *
 * Whoever installs a module adding a provider should not have to edit a config file, and they
 * should not have to guess the callback address either — it is on the screen, ready to be pasted
 * into the settings of the application at the provider.
 */
final readonly class ExternalProvidersController
{
    private const URL = '/admin/auth/providers';

    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private GetExternalProvidersUseCase $getProviders,
        private UpdateExternalProvidersUseCase $updateProviders,
        private SiteBaseUrl $siteUrl,
    ) {
    }

    public function index(Request $request): ViewResponse
    {
        $title = __('Sign-in services');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/external-providers.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'sec_menu'        => ['auth_providers' => true],
                'form_action'     => self::URL,
                // The same address the flow sends, resolved in one place: a callback registered
                // at the provider that differs from the one we send is refused by it.
                'providers'       => $this->getProviders->execute($this->siteUrl->resolve($request)),
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    public function save(Request $request): void
    {
        /** @var array<string, array<string, mixed>> $input */
        $input = $request->request->all('providers');
        $providers = [];

        foreach ($input as $key => $values) {
            $providers[(string) $key] = [
                'client_id'     => (string) ($values['client_id'] ?? ''),
                'client_secret' => (string) ($values['client_secret'] ?? ''),
                'enabled'       => ! empty($values['enabled']),
            ];
        }

        $this->updateProviders->execute($providers);
        $this->session->flash('success_message', __('Changes saved successfully'));

        redirect(self::URL);
    }
}
