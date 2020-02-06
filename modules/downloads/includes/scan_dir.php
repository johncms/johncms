<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\FileInfo;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */


set_time_limit(99999);
$do = isset($_GET['do']) ? trim($_GET['do']) : '';
$mod = isset($_GET['mod']) ? (int) ($_GET['mod']) : '';

if ($do === 'clean') {
    // Удаляем отсутствующие файлы
    $query = $db->query('SELECT `id`, `dir`, `name`, `type` FROM `download__files`');

    while ($result = $query->fetch()) {
        if (! file_exists($result['dir'] . '/' . $result['name'])) {
            $req = $db->query("SELECT `id` FROM `download__more` WHERE `refid` = '" . $result['id'] . "'");

            while ($res = $req->fetch()) {
                @unlink($result['dir'] . '/' . $res['name']);
            }

            $db->exec("DELETE FROM `download__bookmark` WHERE `file_id`='" . $result['id'] . "'");
            $db->exec("DELETE FROM `download__more` WHERE `refid` = '" . $result['id'] . "'");
            $db->exec("DELETE FROM `download__comments` WHERE `sub_id`='" . $result['id'] . "'");
            $db->exec("DELETE FROM `download__files` WHERE `id` = '" . $result['id'] . "' LIMIT 1");
        }
    }

    $query = $db->query('SELECT `id`, `dir`, `name` FROM `download__category`');

    while ($result = $query->fetch()) {
        if (! is_dir($result['dir'])) {
            $arrayClean = [];
            $req = $db->query("SELECT `id` FROM `download__files` WHERE `refid` = '" . $result['id'] . "'");
            while ($res = $req->fetch()) {
                $arrayClean[] = $res['id'];
            }

            if (! empty($arrayClean)) {
                $idClean = implode(',', $arrayClean);
                $db->exec('DELETE FROM `download__bookmark` WHERE `file_id` IN (' . $idClean . ')');
                $db->exec('DELETE FROM `download__comments` WHERE `sub_id` IN (' . $idClean . ')');
                $db->exec('DELETE FROM `download__more` WHERE `refid` IN (' . $idClean . ')');
            }
            $db->exec("DELETE FROM `download__files` WHERE `refid` = '" . $result['id'] . "'");
            $db->exec("DELETE FROM `download__category` WHERE `id` = '" . $result['id'] . "'");
        }
    }

    $req_down = $db->query('SELECT `dir`, `name`, `id` FROM `download__category`');

    while ($res_down = $req_down->fetch()) {
        $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir']) . "%'")->fetchColumn();
        $db->exec("UPDATE `download__category` SET `total` = '${dir_files}' WHERE `id` = '" . $res_down['id'] . "'");
    }

    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Remove missing files'),
            'type'          => 'alert-success',
            'message'       => __('Database successfully updated'),
            'back_url'      => '?id=' . $id,
            'back_url_name' => __('Back'),
        ]
    );
} else {
    // Обновление файлов
    if ($id) {
        $cat = $db->query("SELECT `dir`, `name`, `rus_name` FROM `download__category` WHERE	`id` = '" . $id . "' LIMIT 1");
        $res_down_cat = $cat->fetch();
        $scan_dir = $res_down_cat['dir'];

        if (! $cat->rowCount() || ! is_dir($scan_dir)) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Error'),
                    'type'          => 'alert-success',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => $url,
                    'back_url_name' => __('Downloads'),
                ]
            );
            exit;
        }
    } else {
        $scan_dir = $files_path;
    }

    if (isset($_GET['yes'])) {
        // Сканирование папок
        $array_dowm = [];
        $array_id = [];
        $array_more = [];
        $query = $db->query('SELECT `dir`, `name`, `id` FROM `download__files`');

        while ($result = $query->fetch()) {
            $array_dowm[] = $result['dir'] . '/' . $result['name'];
            $array_id[$result['dir'] . '/' . $result['name']] = $result['id'];
        }

        $queryCat = $db->query('SELECT `dir`, `id` FROM `download__category`');

        while ($resultCat = $queryCat->fetch()) {
            $array_dowm[] = $resultCat['dir'];
            $array_id[$resultCat['dir']] = $resultCat['id'];
        }

        $query_more = $db->query('SELECT `name` FROM `download__more`');

        while ($result_more = $query_more->fetch()) {
            $array_more[] = $result_more['name'];
        }

        $i = 0;
        $i_two = 0;
        $i_three = 0;
        $arr_scan_dir = [];

        $ignore_files = ['name.dat', 'index.php'];
        $fileSPLObjects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scan_dir), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fileSPLObjects as $fullFileName => $fileSPLObject) {
            /** @var $fileSPLObject SplFileObject */
            $file_name = $fileSPLObject->getFilename();
            if (in_array($file_name, $ignore_files, true) || strpos($file_name, '.') === 0) {
                continue;
            }
            $arr_scan_dir[] = $fullFileName;
        }

        if (! empty($arr_scan_dir)) {
            $stmt_c = $db->prepare(
                "
                        INSERT INTO `download__category`
                        (`refid`, `dir`, `sort`, `name`, `field`, `rus_name`, `text`, `desc`)
                        VALUES (?, ?, ?, ?, 0, ?, '', '')
                    "
            );

            $stmt_m = $db->prepare(
                '
                        INSERT INTO `download__more`
                        (`refid`, `time`, `name`, `rus_name`, `size`)
                        VALUES (?, ?, ?, ?, ?)
                    '
            );

            $stmt_f = $db->prepare(
                "
                        INSERT INTO `download__files`
                        (`refid`, `dir`, `time`, `name`, `text`, `rus_name`, `type`, `user_id`, `about`, `desc`)
                        VALUES (?, ?, ?, ?, 'Download', ?, 2, ?, '', '')
                    "
            );

            asort($arr_scan_dir);
            $scan = array_map(
                static function ($path) {
                    $file_info = new FileInfo($path);
                    return $file_info->getCleanPath();
                },
                $arr_scan_dir
            );
            asort($scan);

            foreach ($arr_scan_dir as $key => $val) {
                if ($scan[$key] != $arr_scan_dir[$key]) {
                    if (is_dir($arr_scan_dir[$key]) && ! is_dir($scan[$key])) {
                        mkdir($scan[$key]);
                        $arr_old_dir[] = $arr_scan_dir[$key];
                    } elseif (! file_exists($scan[$key])) {
                        if (copy($arr_scan_dir[$key], $scan[$key])) {
                            unlink($arr_scan_dir[$key]);
                        }
                    }
                }
                $arr_orig_name = explode(DIRECTORY_SEPARATOR, $arr_scan_dir[$key]);
                if (! in_array($scan[$key], $array_dowm, true)) {
                    if (is_dir($scan[$key])) {
                        $name = basename($scan[$key]);
                        $orig_name = array_pop($arr_orig_name);
                        $dir = dirname($scan[$key]);
                        $refid = isset($array_id[$dir]) ? (int) $array_id[$dir] : 0;
                        $sort = isset($sort) ? ++$sort : time();

                        $stmt_c->execute(
                            [
                                $refid,
                                $dir . '/' . $name,
                                $sort,
                                $name,
                                $orig_name,
                            ]
                        );

                        $array_id[$dir . '/' . $name] = $db->lastInsertId();
                        ++$i;
                    } else {
                        $name = basename($scan[$key]);
                        $orig_name = array_pop($arr_orig_name);
                        if (preg_match('/^file(\d+)_/', $name)) {
                            if (! in_array($name, $array_more, true)) {
                                $refid = (int) str_replace('file', '', $name);
                                $name_link = htmlspecialchars(
                                    mb_substr(
                                        str_replace(
                                            'file' . $refid . '_',
                                            __('Download') . ' ',
                                            $name
                                        ),
                                        0,
                                        200
                                    )
                                );
                                $size = filesize($scan[$key]);

                                $stmt_f->execute(
                                    [
                                        $refid,
                                        time(),
                                        $name,
                                        $name_link,
                                        $size,
                                    ]
                                );

                                ++$i_two;
                            }
                        } else {
                            $isFile = $start ? is_file($scan[$key]) : true;
                            if ($isFile) {
                                $dir = dirname($scan[$key]);
                                $refid = (int) $array_id[$dir];

                                $stmt_f->execute(
                                    [
                                        $refid,
                                        $dir,
                                        time(),
                                        $name,
                                        $orig_name,
                                        $user->id,
                                    ]
                                );

                                if ($start) {
                                    $fileId = $db->lastInsertId();
                                    $screenFile = false;

                                    if (is_file($scan[$key] . '.jpg')) {
                                        $screenFile = $scan[$key] . '.jpg';
                                    } elseif (is_file($scan[$key] . '.gif')) {
                                        $screenFile = $scan[$key] . '.gif';
                                    } elseif (is_file($scan[$key] . '.png')) {
                                        $screenFile = $scan[$key] . '.png';
                                    }

                                    if ($screenFile) {
                                        $screens_path = UPLOAD_PATH . 'downloads/screen/';
                                        $is_dir = mkdir($screens_path . '/' . $fileId, 0777);

                                        if ($is_dir === true) {
                                            @chmod($screens_path . '/' . $fileId, 0777);
                                        }

                                        @copy($screenFile, $screens_path . '/' . $fileId . '/' . str_replace($scan[$key], $fileId, $screenFile));
                                        unlink($screenFile);
                                    }

                                    if (is_file($scan[$key] . '.txt')) {
                                        @copy($scan[$key] . '.txt', DOWNLOADS . 'about/' . $fileId . '.txt');
                                        unlink($scan[$key] . '.txt');
                                    }
                                }

                                ++$i_three;
                            }
                        }
                    }
                }
            }

            if (! empty($arr_old_dir)) {
                arsort($arr_old_dir);
                array_map('rmdir', $arr_old_dir);
            }

            $stmt_c = null;
            $stmt_m = null;
            $stmt_f = null;
        }

        if ($id) {
            $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down_cat['dir'] . '/' . $res_down_cat['name']) . "%'")->fetchColumn();
            $db->exec("UPDATE `download__category` SET `total` = '${dir_files}' WHERE `id` = '" . $id . "'");
        } else {
            $req_down = $db->query('SELECT `dir`, `name`, `id` FROM `download__files` WHERE `type` = 1');

            while ($res_down = $req_down->fetch()) {
                $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir'] . '/' . $res_down['name']) . "%'")->fetchColumn();
                $db->exec("UPDATE `download__category` SET `total` = '${dir_files}' WHERE `id` = '" . $res_down['id'] . "'");
            }
        }
        $updated_info = [
            'categories'       => $i,
            'files'            => $i_three,
            'additional_files' => $i_two,
        ];
        $select_mode = false;
    } else {
        $select_mode = true;
    }

    echo $view->render(
        'downloads::scan_dir',
        [
            'title'        => __('Update'),
            'page_title'   => __('Update'),
            'id'           => $id,
            'urls'         => $urls,
            'updated_info' => $updated_info ?? [],
            'select_mode'  => $select_mode,
            'back_url'     => '?id=' . $id,
            'back_name'    => __('Back'),
        ]
    );
}
