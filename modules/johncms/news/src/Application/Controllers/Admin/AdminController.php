<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Http\Session;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdminController
{
    public function __construct(
        private NavChain $navChain,
        private Session $session,
    ) {
    }

    /**
     * Module settings page.
     *
     * @param Request $request
     * @return Response
     */
    public function settings(Request $request): Response | ViewResponse
    {
        $this->navChain->add(__('News'), '/admin/news/');

        $data = [
            'title'       => __('Settings'),
            'page_title'  => __('Settings'),
            'back_url'    => '/admin/news/',
            'form_action' => '/admin/news/settings/',
            'message'     => '',
        ];
        $this->navChain->add($data['page_title']);

        if ($request->getMethod() === 'POST') {
            $config = [
                'homepage_show'     => (bool) $request->body('homepage_show'),
                'homepage_quantity' => $request->bodyInt('homepage_quantity', 3),
                'homepage_days'     => $request->bodyInt('homepage_days'),

                'title'            => $request->body('title', ''),
                'meta_keywords'    => $request->body('meta_keywords', ''),
                'meta_description' => $request->body('meta_description', ''),

                'section_title'            => $request->body('section_title', ''),
                'section_meta_keywords'    => $request->body('section_meta_keywords', ''),
                'section_meta_description' => $request->body('section_meta_description', ''),

                'article_title'            => $request->body('article_title', ''),
                'article_meta_keywords'    => $request->body('article_meta_keywords', ''),
                'article_meta_description' => $request->body('article_meta_description', ''),
            ];

            $configFile = "<?php\n\n" . 'return ' . var_export(['news' => $config], true) . ";\n";
            if (! file_put_contents(CONFIG_PATH . 'autoload/news.local.php', $configFile)) {
                return new Response('ERROR: Can not write news.local.php');
            }
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            $this->session->flash('message', __('Settings saved!'));
            return new RedirectResponse('/admin/news/settings/');
        }

        $flashMessage = $this->session->getFlash('message');
        if (! empty($flashMessage)) {
            $data['message'] = $flashMessage;
        }

        // Стандартные настройки
        $default_settings = [
            'title'            => '',
            'meta_keywords'    => '',
            'meta_description' => '',

            'section_title'            => '',
            'section_meta_keywords'    => '',
            'section_meta_description' => '',

            'article_title'            => '',
            'article_meta_keywords'    => '',
            'article_meta_description' => '',
        ];

        $config = config('news') ?? [];
        $data['current_settings'] = array_merge($default_settings, $config);

        return new ViewResponse('@news/admin/settings.twig', $this->menu(__('Settings')) + $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['news' => true],
        ];
    }
}
