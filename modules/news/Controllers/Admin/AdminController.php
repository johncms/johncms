<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace News\Controllers\Admin;

use Admin\Controllers\BaseAdminController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\System\Http\Request;
use News\Models\NewsArticle;
use News\Models\NewsSection;
use News\Utils\Helpers;

class AdminController extends BaseAdminController
{
    protected $module_name = 'news';

    protected $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];

        $this->render->addData(
            [
                'title'       => __('News'),
                'page_title'  => __('News'),
                'module_menu' => ['news' => true],
            ]
        );
        $this->nav_chain->add(__('News'), '/admin/news/');
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
        $this->nav_chain->add($title, '/admin/news/content/');

        if (! empty($section_id)) {
            try {
                $current_section = (new NewsSection())->findOrFail($section_id);
                $title = $current_section->name;
                Helpers::buildAdminBreadcrumbs($current_section->parentSection);
                // Adding the current section to the navigation chain
                $this->nav_chain->add($current_section->name);
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        $data = [];
        if (! empty($_SESSION['success_message'])) {
            $data['messages'] = htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']);
        }

        if (empty($section_id)) {
            $data['sections'] = (new NewsSection())->where('parent', $section_id)->get();
            $data['articles'] = (new NewsArticle())->where('section_id', $section_id)->orderByDesc('id')->paginate();
        } else {
            $data['sections'] = (new NewsSection())->where('parent', $section_id)->get();
            $data['articles'] = (new NewsArticle())->where('section_id', $section_id)->orderByDesc('id')->paginate();
        }

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
     * @return string
     */
    public function settings(Request $request): string
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
        $this->nav_chain->add($data['page_title']);

        if ($request->getMethod() === 'POST') {
            $config = [
                'ckfinder_license_key'  => $request->getPost('ckfinder_license_key', '', FILTER_SANITIZE_STRING),
                'ckfinder_license_name' => $request->getPost('ckfinder_license_name', '', FILTER_SANITIZE_STRING),

                'title'            => $request->getPost('title', '', FILTER_SANITIZE_STRING),
                'meta_keywords'    => $request->getPost('meta_keywords', '', FILTER_SANITIZE_STRING),
                'meta_description' => $request->getPost('meta_description', '', FILTER_SANITIZE_STRING),

                'section_title'            => $request->getPost('section_title', '', FILTER_SANITIZE_STRING),
                'section_meta_keywords'    => $request->getPost('section_meta_keywords', '', FILTER_SANITIZE_STRING),
                'section_meta_description' => $request->getPost('section_meta_description', '', FILTER_SANITIZE_STRING),

                'article_title'            => $request->getPost('article_title', '', FILTER_SANITIZE_STRING),
                'article_meta_keywords'    => $request->getPost('article_meta_keywords', '', FILTER_SANITIZE_STRING),
                'article_meta_description' => $request->getPost('article_meta_description', '', FILTER_SANITIZE_STRING),
            ];

            $configFile = "<?php\n\n" . 'return ' . var_export(['news' => $config], true) . ";\n";
            if (! file_put_contents(CONFIG_PATH . 'autoload/news.local.php', $configFile)) {
                echo 'ERROR: Can not write news.local.php';
                exit;
            }
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            $_SESSION['message'] = __('Settings saved!');
            header('Location: /admin/news/settings/');
            exit;
        }

        if (! empty($_SESSION['message'])) {
            $data['message'] = htmlspecialchars($_SESSION['message']);
            unset($_SESSION['message']);
        }

        // Стандартные настройки
        $default_settings = [
            'ckfinder_license_key'  => '',
            'ckfinder_license_name' => '',

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
