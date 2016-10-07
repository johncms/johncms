<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

// Обновление файлов
if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    set_time_limit(99999);
    $do = isset($_GET['do']) ? trim($_GET['do']) : '';
    $mod = isset($_GET['mod']) ? intval($_GET['mod']) : '';

    switch ($do) {
        case 'clean':
            // Удаляем отсутствующие файлы
            $query = $db->query("SELECT `id`, `dir`, `name`, `type` FROM `download__files`");

            while ($result = $query->fetch()) {
                if (!file_exists($result['dir'] . '/' . $result['name'])) {
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

            $query = $db->query("SELECT `id`, `dir`, `name` FROM `download__category`");

            while ($result = $query->fetch()) {
                if (!file_exists($result['dir'])) {
                    $arrayClean = [];
                    $req = $db->query("SELECT `id` FROM `download__files` WHERE `refid` = '" . $result['id'] . "'");

                    while ($res = $req->fetch()) {
                        $arrayClean = $res['id'];
                    }

                    $idClean = implode(',', $arrayClean);
                    $db->exec("DELETE FROM `download__bookmark` WHERE `file_id` IN (" . $idClean . ")");
                    $db->exec("DELETE FROM `download__comments` WHERE `sub_id` IN (" . $idClean . ")");
                    $db->exec("DELETE FROM `download__more` WHERE `refid` IN (" . $idClean . ")");
                    $db->exec("DELETE FROM `download__files` WHERE `refid` = '" . $result['id'] . "'");
                    $db->exec("DELETE FROM `download__category` WHERE `id` = '" . $result['id'] . "'");
                }
            }

            $req_down = $db->query("SELECT `dir`, `name`, `id` FROM `download__category`");

            while ($res_down = $req_down->fetch()) {
                $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir']) . "%'")->fetchColumn();
                $db->exec("UPDATE `download__category` SET `total` = '$dir_files' WHERE `id` = '" . $res_down['id'] . "'");
            }

            $db->query("OPTIMIZE TABLE `download__bookmark`, `download__files`, `download__comments`,`download__more`");

            echo '<div class="phdr"><b>' . _t('Remove missing files') . '</b></div>' .
                '<div class="rmenu"><p>' . _t('Database successfully updated') . '</p></div>' .
                '<div class="phdr"><a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
            break;

        default:
            // Обновление файлов
            if ($id) {
                $cat = $db->query("SELECT `dir`, `name`, `rus_name` FROM `download__category` WHERE	`id` = '" . $id . "' LIMIT 1");
                $res_down_cat = $cat->fetch();
                $scan_dir = $res_down_cat['dir'];

                if (!$cat->rowCount() || !is_dir($scan_dir)) {
                    echo _t('The directory does not exist') . ' <a href="?">' . _t('Downloads') . '</a>';
                    exit;
                }
            } else {
                $scan_dir = $files_path;
            }

            echo '<div class="phdr"><b>' . _t('Update Files') . '</b>' . ($id ? ': ' . htmlspecialchars($res_down_cat['rus_name']) : '') . '</div>';

            if (isset($_GET['yes'])) {
                // Сканирование папок
                $array_dowm = [];
                $array_id = [];
                $array_more = [];
                $query = $db->query("SELECT `dir`, `name`, `id` FROM `download__files`");

                while ($result = $query->fetch()) {
                    $array_dowm[] = $result['dir'] . '/' . $result['name'];
                    $array_id[$result['dir'] . '/' . $result['name']] = $result['id'];
                }

                $queryCat = $db->query("SELECT `dir`, `id` FROM `download__category`");

                while ($resultCat = $queryCat->fetch()) {
                    $array_dowm[] = $resultCat['dir'];
                    $array_id[$resultCat['dir']] = $resultCat['id'];
                }

                $query_more = $db->query("SELECT `name` FROM `download__more`");

                while ($result_more = $query_more->fetch()) {
                    $array_more[] = $result_more['name'];
                }

                $array_scan = [];

                function scan_dir($dir = '')
                {
                    static $array_scan;
                    global $mod;
                    $arr_dir = glob($dir . '/*');

                    foreach ($arr_dir as $val) {
                        if (is_dir($val)) {
                            $array_scan[] = $val;
                            if (!$mod) {
                                scan_dir($val);
                            }
                        } else {
                            $file_name = basename($val);
                            if ($file_name != '.' && $file_name != '..' && $file_name != 'index.php' && $file_name != '.htaccess' && $file_name != '.svn') {
                                $array_scan[] = $val;
                            }
                        }
                    }

                    return $array_scan;
                }

                $i = 0;
                $i_two = 0;
                $i_three = 0;
                $arr_scan_dir = scan_dir($scan_dir);

                if ($arr_scan_dir) {
                    $stmt_c = $db->prepare("
                        INSERT INTO `download__category`
                        (`refid`, `dir`, `sort`, `name`, `field`, `rus_name`, `text`, `desc`)
                        VALUES (?, ?, ?, ?, 0, ?, '', '')
                    ");

                    $stmt_m = $db->prepare("
                        INSERT INTO `download__more`
                        (`refid`, `time`, `name`, `rus_name`, `size`)
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $stmt_f = $db->prepare("
                        INSERT INTO `download__files`
                        (`refid`, `dir`, `time`, `name`, `text`, `rus_name`, `type`, `user_id`)
                        VALUES (?, ?, ?, ?, 'Download', ?, 2, ?)
                    ");

                    foreach ($arr_scan_dir as $val) {
                        if (!in_array($val, $array_dowm)) {
                            if (is_dir($val)) {
                                $name = basename($val);
                                $dir = dirname($val);
                                $refid = isset($array_id[$dir]) ? (int)$array_id[$dir] : 0;
                                $sort = isset($sort) ? ($sort + 1) : time();

                                $stmt_c->execute([
                                    $refid,
                                    $dir . "/" . $name,
                                    $sort,
                                    $name,
                                    $name,
                                ]);

                                $array_id[$dir . "/" . $name] = $db->lastInsertId();

                                ++$i;
                            } else {
                                $name = basename($val);
                                if (preg_match("/^file([0-9]+)_/", $name)) {
                                    if (!in_array($name, $array_more)) {
                                        $refid = (int)str_replace('file', '', $name);
                                        $name_link = htmlspecialchars(mb_substr(str_replace('file' . $refid . '_', _t('Download') . ' ', $name), 0, 200));
                                        $size = filesize($val);

                                        $stmt_m->execute([
                                            $refid,
                                            time(),
                                            $name,
                                            $name_link,
                                            $size,
                                        ]);

                                        ++$i_two;
                                    }
                                } else {
                                    $isFile = $start ? is_file($val) : true;
                                    if ($isFile) {
                                        $dir = dirname($val);
                                        $refid = (int)$array_id[$dir];

                                        $stmt_f->execute([
                                            $refid,
                                            $dir,
                                            time(),
                                            $name,
                                            $name,
                                            $user_id,
                                        ]);

                                        if ($start) {
                                            $fileId = $db->lastInsertId();
                                            $screenFile = false;

                                            if (is_file($val . '.jpg')) {
                                                $screenFile = $val . '.jpg';
                                            } elseif (is_file($val . '.gif')) {
                                                $screenFile = $val . '.gif';
                                            } elseif (is_file($val . '.png')) {
                                                $screenFile = $val . '.png';
                                            }

                                            if ($screenFile) {
                                                $is_dir = mkdir($screens_path . '/' . $fileId, 0777);

                                                if ($is_dir == true) {
                                                    @chmod($screens_path . '/' . $fileId, 0777);
                                                }

                                                @copy($screenFile, $screens_path . '/' . $fileId . '/' . str_replace($val, $fileId, $screenFile));
                                                unlink($screenFile);
                                            }

                                            if (is_file($val . '.txt')) {
                                                @copy($val . '.txt', $down_path . '/about/' . $fileId . '.txt');
                                                unlink($val . '.txt');
                                            }
                                        }

                                        ++$i_three;
                                    }
                                }
                            }
                        }
                    }

                    $stmt_c = null;
                    $stmt_m = null;
                    $stmt_f = null;
                }

                if ($id) {
                    $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down_cat['dir'] . '/' . $res_down_cat['name']) . "%'")->fetchColumn();
                    $db->exec("UPDATE `download__files` SET `total` = '$dir_files' WHERE `id` = '" . $id . "'");
                } else {
                    $req_down = $db->query("SELECT `dir`, `name`, `id` FROM `download__files` WHERE `type` = 1");

                    while ($res_down = $req_down->fetch()) {
                        $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir'] . '/' . $res_down['name']) . "%'")->fetchColumn();
                        $db->exec("UPDATE `download__files` SET `total` = '$dir_files' WHERE `id` = '" . $res_down['id'] . "'");
                    }
                }

                echo '<div class="menu"><b>' . _t('Added') . ':</b><br>' .
                    _t('Categories') . ': ' . $i . '<br>' .
                    _t('Files') . ': ' . $i_three . '<br>' .
                    _t('Additional Files') . ': ' . $i_two . '</div>';

                if ($start) {
                    echo '<div class="gmenu"><a href="?act=scan_about&amp;id=' . $id . '">' . _t('Update Descriptions') . '</div>';
                }

                echo '<div class="rmenu">' .
                    '<a href="?act=scan_dir&amp;do=clean&amp;id=' . $id . '">' . _t('Remove missing files') . '</a><br>' .
                    '<a href="?act=recount&amp;do=clean&amp;id=' . $id . '">' . _t('Update counters') . '</a></div>';
            } else {
                // Выбор режима обновление
                echo '<div class="menu"><b><a href="?act=scan_dir&amp;yes&amp;id=' . $id . '">' . ($id ? _t('Update all folders from the current') : _t('Update entire Downloads')) . '</a></b>' .
                    ($id ? '<br><a href="?act=scan_dir&amp;yes&amp;id=' . $id . '&amp;mod=1">' . _t('Update only current folder') . '</a>' : '') . '</div>';

                if ($id) {
                    echo '<div class="rmenu"><a href="?act=scan_dir&amp;yes">' . _t('Update entire Downloads') . '</a></div>';
                }

                echo '<div class="phdr"><b>' . _t('Update v.2') . '</b> beta</div>' .
                    '<div class="topmenu">' . _t('After this update, you must update the description') . '</div><div class="menu">' .
                    '<a href="?act=scan_dir&amp;yes&amp;id=' . $id . '&amp;start=1"><b>' . ($id ? _t('Update all folders from the current') : _t('Update entire Downloads')) . '</b></a> ' .
                    ($id ? '<br><a href="?act=scan_dir&amp;yes&amp;id=' . $id . '&amp;mod=1&amp;start=1">' . _t('Update only current folder') . '</a>' : '') .
                    '<div class="sub"><small>' . _t('This action not only updates the file, but will distribute screenshots and descriptions for folders<br>Sample file: file.jar, file.jar.txt, file.jar.jpg (gif, png) (only 1 screenshot)<br><b>ATTENTION:</b> load this update is much higher') . '</small></div>' .
                    '</div><div class="rmenu">';

                if ($id) {
                    echo ' <a href="?act=scan_dir&amp;yes&amp;start=1">' . _t('Update entire Downloads') . '</a><br>';
                }

                echo '<a href="?act=scan_dir&amp;do=clean&amp;id=' . $id . '">' . _t('Remove missing files') . '</a></div>';
            }
            echo '<div class="phdr"><a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
    }
}
