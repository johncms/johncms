<?php

declare(strict_types=1);

namespace Johncms\Modules\Redirect\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Redirect\Application\UseCases\RedirectByIdUseCase;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class RedirectController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private RedirectByIdUseCase $redirectByIdUseCase,
    ) {
        $this->controllerContext->initModule('redirect');
    }

    public function __invoke(Request $request): Response|ViewResponse
    {
        $id = $request->queryInt('id', 0);
        $url = $request->queryParam('url', '');

        $url = $url !== '' ? strip_tags(rawurldecode(trim($url))) : '';

        if ($id) {
            return $this->handleById($id);
        }

        if ($url !== '') {
            return $this->handleByUrl($request, $url);
        }

        // Если нет ни id ни url, можно вернуть 404 или редирект на главную
        return new RedirectResponse('/');
    }

    private function handleById(int $id): Response
    {
        $redirectUrl = $this->redirectByIdUseCase->execute($id);
        if ($redirectUrl === null) {
            return new RedirectResponse('https://johncms.com/404');
        }

        return new RedirectResponse($redirectUrl);
    }

    private function handleByUrl(Request $request, string $url): Response|ViewResponse
    {
        if ($request->hasBody('submit')) {
            return new RedirectResponse($url);
        }

        $title = __('Redirect to an external link');
        $this->navChain->add($title);

        return new ViewResponse(
            '@redirect/public/index.twig',
            [
                'title'        => $title,
                'page_title'   => $title,
                'redirect_url' => rawurlencode($url),
                'referer'      => $request->headers->get('referer', '/'),
                'url'          => $url,
                'site_host'    => parse_url((string) config('johncms.homeurl', ''), PHP_URL_HOST),
            ]
        );
    }
}
