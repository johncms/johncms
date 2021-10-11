<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\News\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Controller\BaseAdminController;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;
use Johncms\News\Models\NewsArticle;
use Johncms\News\Models\NewsSection;
use Johncms\News\Utils\Helpers;
use Throwable;

class AdminController extends BaseAdminController
{
    protected string $moduleName = 'johncms/news';

    protected array $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->metaTagManager->setAll(__('News'));
        $this->render->addData(['module_menu' => ['news' => true]]);
        $this->navChain->add(__('News'), route('news.admin.index'));
    }

    /**
     * @throws Throwable
     */
    public function index(): void
    {
        echo $this->render->render('news::admin/index');
    }

    /**
     * List of sections and articles
     *
     * @param Session $session
     * @param int $section_id
     * @return string
     * @throws Throwable
     */
    public function section(Session $session, int $section_id = 0): string
    {
        $title = __('Section list');
        $this->navChain->add($title, route('news.admin.section'));

        if (! empty($section_id)) {
            $current_section = (new NewsSection())->findOrFail($section_id);
            $title = $current_section->name;
            Helpers::buildAdminBreadcrumbs($current_section->parentSection);
            // Adding the current section to the navigation chain
            $this->navChain->add($current_section->name);
        }

        $data = [];
        $data['messages'] = $session->getFlash('success_message');
        $data['sections'] = (new NewsSection())
            ->when(! empty($section_id), function (Builder $builder) use ($section_id) {
                return $builder->where('parent', $section_id);
            })
            ->when(empty($section_id), function (Builder $builder) {
                return $builder->where('parent', 0)->orWhereNull('parent');
            })
            ->get();
        $data['articles'] = (new NewsArticle())
            ->when(! empty($section_id), function (Builder $builder) use ($section_id) {
                return $builder->where('section_id', $section_id);
            })
            ->when(empty($section_id), function (Builder $builder) {
                return $builder->where('section_id', 0)->orWhereNull('section_id');
            })
            ->orderByDesc('id')
            ->paginate();
        $data['current_section'] = $section_id;

        $this->metaTagManager->setAll($title);
        return $this->render->render('news::admin/sections', ['data' => $data]);
    }

    /**
     * Module settings page.
     *
     * @param Request $request
     * @param Session $session
     * @return \Johncms\Http\Response\RedirectResponse|string
     * @throws Throwable
     */
    public function settings(Request $request, Session $session): RedirectResponse|string
    {
        $data = [
            'title'       => __('Settings'),
            'page_title'  => __('Settings'),
            'back_url'    => route('news.admin.index'),
            'form_action' => route('news.admin.settingsStore'),
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
                'homepage_show'     => (bool) $request->getPost('homepage_show', false),
                'homepage_quantity' => $request->getPost('homepage_quantity', 3, FILTER_VALIDATE_INT),
                'homepage_days'     => $request->getPost('homepage_days', 0, FILTER_VALIDATE_INT),

                'title'            => $request->getPost('title', ''),
                'meta_keywords'    => $request->getPost('meta_keywords', ''),
                'meta_description' => $request->getPost('meta_description', ''),

                'section_title'            => $request->getPost('section_title', ''),
                'section_meta_keywords'    => $request->getPost('section_meta_keywords', ''),
                'section_meta_description' => $request->getPost('section_meta_description', ''),

                'article_title'            => $request->getPost('article_title', ''),
                'article_meta_keywords'    => $request->getPost('article_meta_keywords', ''),
                'article_meta_description' => $request->getPost('article_meta_description', ''),
            ];

            $configFile = "<?php\n\n" . 'return ' . var_export(['news' => $config], true) . ";\n";
            if (! file_put_contents(CONFIG_PATH . 'autoload/news.local.php', $configFile)) {
                echo 'ERROR: Can not write news.local.php';
                exit;
            }
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            $session->flash('message', __('Settings saved!'));
            return new RedirectResponse(route('news.admin.settingsStore'));
        }

        $data['message'] = $session->getFlash('message');

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

        $config = di('config')['news'] ?? [];
        $data['current_settings'] = array_merge($default_settings, $config);

        // Выводим шаблон настроек уведомлений
        return $this->render->render('news::admin/settings', ['data' => $data]);
    }
}
