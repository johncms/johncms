<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

const ROOT_DIR = ROOT_PATH;

/**
 * @var Johncms\System\Users\User $user
 */

$title = __('Anti-Spyware');
$nav_chain->add($title, '/admin/antispy/');

// Проверяем права доступа
if ($user->rights < 9) {
    echo $view->render(
        'system::pages/result',
        [
            'title'       => $title,
            'type'        => 'alert-danger',
            'message'     => __('Access denied'),
            'admin'       => true,
            'menu_item'   => 'antispy',
            'parent_menu' => 'sec_menu',
        ]
    );
    exit();
}

class scaner // phpcs:ignore
{
    // Сканер - антишпион
    public $scan_folders = [
        '',
        'assets',
        'config',
        'data',
        'modules',
        'system',
        'themes',
        'upload',
    ];

    public $good_files = [];

    public $snap_base = 'security-scanner-snapshot.cache';

    public $snap_files = [];

    public $bad_files = [];

    public $snap = false;

    public $track_files = [];

    private $checked_folders = [];

    private $cache_files = [];

    public function snapscan()
    {
        // Сканирование по образу
        if (file_exists(CACHE_PATH . $this->snap_base)) {
            $filecontents = file(CACHE_PATH . $this->snap_base);

            foreach ($filecontents as $name => $value) {
                $filecontents[$name] = explode('|', trim($value));
                $this->track_files[$filecontents[$name][0]] = $filecontents[$name][1];
            }

            $this->snap = true;
        }

        foreach ($this->scan_folders as $data) {
            $this->scanFiles(ROOT_DIR . $data);
        }
    }

    public function snap()
    {
        // Добавляем снимок надежных файлов в базу
        foreach ($this->scan_folders as $data) {
            $this->scanFiles(ROOT_DIR . $data, true);
        }

        $filecontents = '';

        foreach ($this->snap_files as $idx => $data) {
            $filecontents .= $data['file_path'] . '|' . $data['file_crc'] . "\r\n";
        }

        $filehandle = fopen(CACHE_PATH . $this->snap_base, 'w+');
        fwrite($filehandle, $filecontents);
        fclose($filehandle);
        @chmod(CACHE_PATH . $this->snap_base, 0666);
    }

    public function scanFiles($dir, $snap = false)
    {
        $this->checked_folders[] = $dir . '/';

        if ($dh = @opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if (is_dir($dir . '/' . $file)) {
                    if ($dir != ROOT_DIR) {
                        $this->scanFiles($dir . '/' . $file, $snap);
                    }
                } else {
                    if ($this->snap || $snap) {
                        $templates = '|tpl';
                    } else {
                        $templates = '';
                    }

                    if (preg_match("#.*\.(php|cgi|pl|perl|php3|php4|php5|php6|phtml|py|htaccess" . $templates . ')$#i', $file)) {
                        $folder = str_replace('../..', '.', $dir);
                        $file_size = filesize($dir . '/' . $file);
                        $file_crc = strtoupper(dechex(crc32(file_get_contents($dir . '/' . $file))));
                        $file_date = date('d.m.Y H:i:s', filectime($dir . '/' . $file));

                        if ($snap) {
                            $this->snap_files[] = [
                                'file_path' => $folder . '/' . $file,
                                'file_crc'  => $file_crc,
                            ];
                        } elseif ($this->snap) {
                            if (
                                array_key_exists($folder . '/' . $file, $this->track_files) &&
                                $this->track_files[$folder . '/' . $file] !== $file_crc &&
                                ! in_array($folder . '/' . $file, $this->cache_files)
                            ) {
                                $this->bad_files[] = [
                                    'file_path' => $folder . '/' . $file,
                                    'file_name' => $file,
                                    'file_date' => $file_date,
                                    'type'      => 1,
                                    'file_size' => $file_size,
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
}

$scaner = new scaner();

switch ($mod) {
    case 'snapscan':
        // Сканируем на соответствие ранее созданному снимку
        $scaner->snapscan();
        $title = __('Snapshot scan');
        if (count($scaner->track_files) == 0) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-danger',
                    'message'       => __('Snapshot is not created'),
                    'admin'         => true,
                    'menu_item'     => 'antispy',
                    'parent_menu'   => 'sec_menu',
                    'back_url'      => '?act=antispy&amp;mod=snap',
                    'back_url_name' => __('Create snapshot'),
                ]
            );
        } elseif (count($scaner->bad_files)) {
            $data['total'] = count($scaner->bad_files);
            $data['bad_files'] = $scaner->bad_files;
            $data['back_url'] = '/admin/antispy/';
            echo $view->render(
                'admin::antispy_scan_result',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Excellent!<br>All files are consistent with previously made image.'),
                    'admin'         => true,
                    'menu_item'     => 'antispy',
                    'parent_menu'   => 'sec_menu',
                    'back_url'      => '/admin/antispy/',
                    'back_url_name' => __('Back'),
                ]
            );
        }
        break;

    case 'snap':
        // Создаем снимок файлов
        $title = __('Create snapshot');

        if (isset($_POST['submit'])) {
            $scaner->snap();
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Snapshot successfully created'),
                    'admin'         => true,
                    'menu_item'     => 'antispy',
                    'parent_menu'   => 'sec_menu',
                    'back_url'      => '/admin/antispy/',
                    'back_url_name' => __('Continue'),
                ]
            );
        } else {
            $data['form_action'] = '?mod=snap';
            $data['back_url'] = '/admin/antispy/';
            echo $view->render(
                'admin::antispy_create_confirm',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
        break;

    default:
        // Главное меню Сканера
        echo $view->render(
            'admin::antispy',
            [
                'title'      => $title,
                'page_title' => $title,
            ]
        );
}
