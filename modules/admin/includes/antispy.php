<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

const ROOT_DIR = ROOT_PATH;

/**
 * @var Johncms\System\Users\User $user
 */

// Проверяем права доступа
if ($user->rights < 9) {
    exit(__('Access denied'));
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
                        } else {
                            if ($this->snap) {
                                if ($this->track_files[$folder . '/' . $file] != $file_crc && ! in_array($folder . '/' . $file, $this->cache_files)) {
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
}

$scaner = new scaner();

switch ($mod) {
    case 'scan':
        // Сканируем на соответствие дистрибутиву
        $scaner->scan();
        echo '<div class="phdr"><a href="?act=antispy"><b>' . __('Anti-Spyware') . '</b></a> | ' . __('Distributive scan') . '</div>';

        if (count($scaner->bad_files)) {
            echo '<div class="rmenu"><small>' . __('Distributive contains complementary files<br>Warning! If the files listed below does not pertain to your additional modules and you are not assured of their safety, remove them. They can be dangerous for your site.') . '</small></div>'; // phpcs:ignore
            echo '<div class="menu">';

            foreach ($scaner->bad_files as $idx => $data) {
                echo $data['file_path'] . '<br>';
            }
            echo '</div><div class="phdr">' . __('Total') . ': ' . count($scaner->bad_files) . '</div>';
        } else {
            echo '<div class="gmenu">' . __('<h3>EXCELLENT!!!</h3>List of files corresponds to the distributive') . '</div>';
        }

        echo '<p><a href="?act=antispy&amp;mod=scan">' . __('Rescan') . '</a></p>';
        break;

    case 'snapscan':
        // Сканируем на соответствие ранее созданному снимку
        $scaner->snapscan();
        echo '<div class="phdr"><a href="?act=antispy"><b>' . __('Anti-Spyware') . '</b></a> | ' . __('Snapshot scan') . '</div>';

        if (count($scaner->track_files) == 0) {
            /** @var Johncms\System\Legacy\Tools $tools */
            $tools = di(Johncms\System\Legacy\Tools::class);

            echo $tools->displayError(
                __('Snapshot is not created'),
                '<a href="?act=antispy&amp;mod=snap">' . __('Create snapshot') . '</a>'
            );
        } else {
            if (count($scaner->bad_files)) {
                echo '<div class="rmenu">' . __('Snapshot Inconsistency<br>Warning! You need to pay attention to all files from the list. They have been added or modified since the image created.') . '</div>';
                echo '<div class="menu">';

                foreach ($scaner->bad_files as $idx => $data) {
                    echo $data['file_path'] . '<br>';
                }
                echo '</div>';
            } else {
                echo '<div class="gmenu">' . __('Excellent!<br>All files are consistent with previously made image.') . '</div>';
            }

            echo '<div class="phdr">' . __('Total') . ': ' . count($scaner->bad_files) . '</div>';
        }
        break;

    case 'snap':
        // Создаем снимок файлов
        echo '<div class="phdr"><a href="?act=antispy"><b>' . __('Anti-Spyware') . '</b></a> | ' . __('Create snapshot') . '</div>';

        if (isset($_POST['submit'])) {
            $scaner->snap();
            echo '<div class="gmenu"><p>' . __('Snapshot successfully created') . '</p></div>' .
                '<div class="phdr"><a href="?act=antispy">' . __('Continue') . '</a></div>';
        } else {
            echo '<form action="?act=antispy&amp;mod=snap" method="post">' .
                '<div class="menu"><p>' . __('WARNING!!!<br>Before continuing make sure that all the files have been identified in the scanning mode distribution reliable and contain no unauthorized modifications.') . '</p>' .
                '<p><input type="submit" name="submit" value="' . __('Create snapshot') . '" /></p>' .
                '</div></form>' .
                '<div class="phdr"><small>' . __('This procedure creates a list of all script files to your site, calculates the checksums and writes to the database, for later comparison.') . '</small></div>';
        }
        break;

    default:
        // Главное меню Сканера
        echo '<div class="phdr"><a href="./"><b>' . __('Admin Panel') . '</b></a> | ' . __('Anti-Spyware') . '</div>'
            . '<div class="menu"><p><h3>' . __('Scan mode') . '</h3><ul>'
            . '<li><a href="?act=antispy&amp;mod=snapscan">' . __('Snapshot scan') . '</a><br>'
            . '<small>' . __('Compare the list of files and checksums with pre-made way. Allows you to identify unknow files, and unauthorized changes.') . '</small></li>'
            . '<li><a href="?act=antispy&amp;mod=snap">' . __('Create snapshot') . '</a><br>'
            . '<small>' . __('Takes a snapshot of all script files from the site calculates their checksums and stored in the database.') . '</small></li>'
            . '</ul></p></div><div class="phdr">&#160;</div>';
}

echo '<p>' . ($mod ? '<a href="?act=antispy">' . __('Scanner menu') . '</a><br>' : '') . '<a href="./">' . __('Admin Panel') . '</a></p>';

echo $view->render(
    'system::app/old_content',
    [
        'title' => __('Admin Panel'),
        'content' => ob_get_clean(),
    ]
);
