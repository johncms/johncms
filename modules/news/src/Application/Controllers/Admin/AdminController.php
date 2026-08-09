<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
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
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        $this->navChain->add(__('News'), '/admin/news/');

        return new ViewResponse('@news/admin/index.twig', $this->menu(__('News')));
    }

    /**
     * List of sections and articles
     */
    public function section(int $section_id = 0): ViewResponse
    {
        $this->navChain->add(__('News'), '/admin/news/');

        $title = __('Section list');
        $this->navChain->add($title, '/admin/news/content/');

        if (! empty($section_id)) {
            try {
                $current_section = (new NewsSection())->findOrFail($section_id);
                $title = $current_section->name;
                Helpers::buildAdminBreadcrumbs($current_section->parentSection);
                // Adding the current section to the navigation chain
                $this->navChain->add($current_section->name);
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        $data = ['messages' => ''];
        $flashMessage = $this->session->getFlash('success_message');
        if (! empty($flashMessage)) {
            $data['messages'] = $flashMessage;
        }

        $data['sections'] = (new NewsSection())->where('parent', $section_id)->get();

        $pagination = $this->paginationFactory->create(
            (new NewsArticle())->where('section_id', $section_id)->count()
        );

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $data['articles'] = (new NewsArticle())
            ->where('section_id', $section_id)
            ->orderByDesc('id')
            ->offset($pagination->getOffset())
            ->limit($pagination->getPerPage())
            ->get();
        $data['pagination'] = $pagination->render();
        $data['current_section'] = $section_id;

        return new ViewResponse('@news/admin/content.twig', $this->menu($title) + $data);
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
