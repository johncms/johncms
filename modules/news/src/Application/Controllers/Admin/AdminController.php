<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdminController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('news');

        $this->render->addData(
            [
                'title'       => __('News'),
                'page_title'  => __('News'),
                'module_menu' => ['news' => true],
            ]
        );
        $this->navChain->add(__('News'), '/admin/news/');
    }

    public function index(): void
    {
        echo $this->render->render('news::admin/index');
    }

    /**
     * List of sections and articles
     *
     * @param int $section_id
     * @return string
     */
    public function section(int $section_id = 0): string
    {
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

        $data = [];
        if (! empty($_SESSION['success_message'])) {
            $data['messages'] = htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']);
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

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
            ]
        );
        return $this->render->render('news::admin/sections', ['data' => $data]);
    }

    /**
     * Module settings page.
     *
     * @param Request $request
     * @return Response
     */
    public function settings(Request $request): Response
    {
        $data = [
            'title'       => __('Settings'),
            'page_title'  => __('Settings'),
            'back_url'    => '/admin/news/',
            'form_action' => '/admin/news/settings/',
            'message'     => '',
        ];
        $this->render->addData(
            [
                'title'      => $data['title'],
                'page_title' => $data['page_title'],
            ]
        );
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

            $_SESSION['message'] = __('Settings saved!');
            return new RedirectResponse('/admin/news/settings/');
        }

        if (! empty($_SESSION['message'])) {
            $data['message'] = htmlspecialchars($_SESSION['message']);
            unset($_SESSION['message']);
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

        // Выводим шаблон настроек уведомлений
        return new Response($this->render->render('news::admin/settings', ['data' => $data]));
    }
}
