<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = $lng_profile['friends'];
require('../incfiles/head.php');
$set_mail = unserialize($set['setting_mail']);

if (!isset($set_mail['cat_friends'])) {
    $set_mail['cat_friends'] = 0;
}

if ($id && $id != $user_id && $do) {
    echo '<div class="phdr"><h3>' . $lng_profile['friends'] . '</h3></div>';
    $result = $db->query("SELECT * FROM `cms_contact` LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id` WHERE `cms_contact`.`user_id`='$user_id' AND `cms_contact`.`from_id`='$id'")->fetch();

    switch ($do) {
        case 'add':
            $fr = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND ((`from_id`='$id' AND `user_id`='$user_id') OR (`from_id`='$user_id' AND `user_id`='$id'))")->fetchColumn();
            if ($fr != 2) {
                if (isset($_POST['submit'])) {
                    $fr_out = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND `user_id`='$user_id' AND `from_id`='$id'")->fetchColumn();
                    if ($fr_out) {
                        echo functions::display_error($lng_profile['already_demand']);
                        echo '<div class="bmenu"><a href="profile.php?user=' . $id . '">' . $lng['back'] . '</a></div>';
                        require_once('../incfiles/end.php');
                        exit;
                    }

                    $fr_in = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND `from_id`='$user_id' AND `user_id`='$id'")->fetchColumn();
                    if ($fr_in) {
                        echo functions::display_error($lng_profile['offer_already']);
                        echo '<div class="bmenu"><a href="profile.php?user=' . $id . '">' . $lng['back'] . '</a></div>';
                        require_once('../incfiles/end.php');
                        exit;
                    }

                    $friends = isset($_POST['friends']) && $_POST['friends'] >= 0 && $_POST['friends'] <= 6 && $set_mail['cat_friends'] ? abs(intval($_POST['friends'])) : 0;
                    $arr_fr = array(
                        1 => $lng_profile['you_my_friends'],
                        $lng_profile['you_my_classfriends'],
                        $lng_profile['you_my_colleagues'],
                        $lng_profile['you_my_best_friends'],
                        $lng_profile['you_my_classmates'],
                        $lng_profile['you_my_relatives']);
                    $my = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `from_id`='$id'")->fetchColumn();

                    if ($my != 0) {
                        $db->exec("UPDATE `cms_contact` SET `type`='2', `time`='" . time() . "', `man`='" . $friends . "' WHERE `user_id`='$user_id' AND `from_id`='$id'");
                    } else {
                        $db->exec("INSERT INTO `cms_contact` SET `user_id`='$user_id', `from_id`='$id', `time`='" . time() . "', `type`='2', `man`='" . $friends . "'");
                        $db->exec("INSERT INTO `cms_contact` SET `user_id`='$id', `from_id`='$user_id', `time`='" . time() . "'");
                    }

                    $user_set = unserialize($user['set_user']);
                    $lng_set = isset($user_set['lng']) && in_array($user_set['lng'], core::$lng_list) ? $user_set['lng'] : core::$lng_iso;
                    $lng_tmp = core::load_lng('profile', $lng_set);
                    $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $user_id . ']' . $user['name'] . '[/url] ' . $lng_tmp['offers_friends'] . ' [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=ok&id=' . $user_id . ']' . $lng_tmp['confirm'] . '[/url] | [url=' . $home . '/users/profile.php?act=friends&do=no&id=' . $user_id . ']' . $lng_tmp['decline'] . '[/url]';

                    $stmt = $db->prepare("INSERT INTO `cms_mail` SET
					   `user_id` = '$user_id', 
					   `from_id` = '$id',
					   `text` = ?,
					   `time` = '" . time() . "',
					   `sys` = '1',
					   `them` = ?
                    ");
                    $stmt->execute([
                        $text,
                        $lng_profile['friendship']
                    ]);


                    if ($friends) {
                        $text1 = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $user_id . ']' . $user['name'] . '[/url] ' . $arr_fr[$friends] . ' [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=okfriends&id=' . $user_id . ']' . $lng_profile['confirm'] . '[/url]';
                        $stmt = $db->prepare("INSERT INTO `cms_mail` SET
						`user_id` = '$user_id', 
						`from_id` = '$id',
						`text` = ?,
						`time` = '" . time() . "',
						`sys` = '1',
						`them` = ?");
                        $stmt->execute([
                            $text1,
                            $lng_profile['friendship']
                        ]);
                    }
                    echo '<div class="rmenu">' . $lng_profile['demand_friends_sent'] . '</div>';
                } else {
                    if (!functions::is_ignor($id) && !isset($ban['1']) && !isset($ban['3'])) {
                        echo '<div class="gmenu"><form action="profile.php?act=friends&amp;do=add&amp;id=' . $id . '" method="post"><div>
					' . $lng_profile['really_offer_friendship'] . '<br />
					' . ($set_mail['cat_friends'] ? '<input type="radio" value="6" name="friends" />&#160;' . $lng_profile['relative'] . '<br />
					<input type="radio" value="5" name="friends" />&#160;' . $lng_profile['classmate'] . '<br />
					<input type="radio" value="4" name="friends" />&#160;' . $lng_profile['best_friend'] . '<br />
					<input type="radio" value="3" name="friends" />&#160;' . $lng_profile['colleague'] . '<br />
					<input type="radio" value="2" name="friends" />&#160;' . $lng_profile['classfriend'] . '<br />
					<input type="radio" value="1" name="friends" />&#160;' . $lng_profile['friend'] . '<br />' : '') . '
					<input type="submit" name="submit" value="' . $lng_profile['confirm'] . '"/>
					</div></form></div>';
                    } else {
                        echo '<div class="rmenu">' . $lng_profile['error_frienship_you_in_ignor'] . '</div>';
                    }
                }
            } else {
                echo functions::display_error($lng_profile['user_already_friend']);
            }
            break;

        case 'cancel':
            $fr = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND ((`from_id`='$id' AND `user_id`='$user_id') OR (`from_id`='$user_id' AND `user_id`='$id'))")->fetchColumn();

            if ($fr != 2) {
                if (isset($_POST['submit'])) {
                    $fr_out = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND `user_id`='$user_id' AND `from_id`='$id'")->fetchColumn();
                    if ($fr_out == 0) {
                        echo functions::display_error($lng_profile['not_demand_friendship']);
                        echo '<div class="bmenu"><a href="profile.php?user=' . $id . '">' . $lng['back'] . '</a></div>';
                        require_once('../incfiles/end.php');
                        exit;
                    }

                    $db->exec("UPDATE `cms_contact` SET
					  `type`='1'
					  WHERE `user_id`='$user_id'
					  AND `from_id`='$id'
					");

                    $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $user_id . ']' . $user['name'] . '[/url] ' . $lng_profile['offers_friends'] . ' [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=ok&id=' . $user_id . ']' . $lng_profile['confirm'] . '[/url] | [url=' . $home . '/users/profile.php?act=friends&do=no&id=' . $user_id . ']' . $lng_profile['decline'] . '[/url]';
                    $stmt = $db->prepare("DELETE FROM `cms_mail` WHERE `text`= ?");
                    $stmt->execute([$text]);
                    echo '<div class="rmenu">' . $lng_profile['demand_cancelled'] . '</div>';
                } else {
                    echo '<div class="gmenu"><form action="profile.php?act=friends&amp;do=cancel&amp;id=' . $id . '" method="post"><div>
					' . $lng_profile['really_demand_cancelled'] . '<br />
					<input type="submit" name="submit" value="' . $lng_profile['confirm'] . '"/>
					</div></form></div>';
                }
            } else {
                echo functions::display_error($lng_profile['already_your_friend']);
            }
            break;

        case 'okfriends':
            if ($set_mail['cat_friends']) {
                $fr = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `from_id`='$id'")->fetchColumn();

                if ($fr) {
                    $res = $db->query("SELECT * FROM `cms_contact` WHERE `user_id`='$id' AND `from_id`='$user_id' LIMIT 1")->fetch();
                    if (isset($_POST['submit'])) {
                        $arr_fr = array(
                            1 => $lng_profile['you_my_friends'],
                            $lng_profile['you_my_classfriends'],
                            $lng_profile['you_my_colleagues'],
                            $lng_profile['you_my_best_friends'],
                            $lng_profile['you_my_classmates'],
                            $lng_profile['you_my_relatives']);

                        $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $id . ']' . $result['name'] . '[/url] ' . $arr_fr[$res['man']] . ' [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=okfriends&id=' . $id . ']' . $lng_profile['confirm'] . '[/url]';
                        $stmt = $db->prepare("DELETE FROM `cms_mail` WHERE `user_id` = '$id' AND `from_id` = '$user_id' AND `text`= ?");
                        $stmt->execute([
                            $text
                        ]);
                        $arr_fr1 = array(
                            1 => $lng_profile['you_friends'],
                            $lng_profile['you_classfriends'],
                            $lng_profile['you_colleagues'],
                            $lng_profile['you_best_friends'],
                            $lng_profile['you_classmates'],
                            $lng_profile['you_relatives']);
                        $db->exec("UPDATE `cms_contact` SET `man`='{$res['man']}' WHERE `user_id`='$user_id' AND `from_id`='$id'");
                        echo '<div class="rmenu">' . $arr_fr1[$res['man']] . ' ' . $result['name'] . '</div>';
                    } else {
                        $arr_fr2 = array(
                            1 => $lng_profile['friend'],
                            $lng_profile['classfriend'],
                            $lng_profile['colleague'],
                            $lng_profile['best_friend'],
                            $lng_profile['classmate'],
                            $lng_profile['relative']);

                        echo '<div class="gmenu"><form action="profile.php?act=friends&amp;do=okfriends&amp;id=' . $id . '" method="post"><div>
						' . $lng_profile['really_okfriends'] . ' ' . $arr_fr2[$res['man']] . '<br />
						<input type="submit" name="submit" value="' . $lng_profile['confirm'] . '"/>
						</div></form></div>';
                    }
                } else {
                    echo functions::display_error($lng['error']);
                }
            } else {
                echo functions::display_error($lng_profile['function_is_disconnected']);
            }
            break;

        case 'ok':
            $fr = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND ((`from_id`='$id' AND `user_id`='$user_id') OR (`from_id`='$user_id' AND `user_id`='$id'))")->fetchColumn();

            if ($fr != 2) {
                if (isset($_POST['submit'])) {
                    $fr_out = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND `user_id`='$id' AND `from_id`='$user_id'")->fetchColumn();

                    if ($fr_out == 0) {
                        echo functions::display_error($lng_profile['not_offers_friendship']);
                        echo '<div class="bmenu"><a href="profile.php?user=' . $id . '">' . $lng['back'] . '</a></div>';
                        require_once('../incfiles/end.php');
                        exit;
                    }

                    $db->exec("UPDATE `cms_contact` SET
					  `type`='2', `friends`='1'
					  WHERE `user_id`='$user_id'
					  AND `from_id`='$id'
					");

                    $db->exec("UPDATE `cms_contact` SET
					  `friends`='1'
					  WHERE `user_id`='$id'
					  AND `from_id`='$user_id'
					");

                    $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $user_id . ']' . $user['name'] . '[/url] ' . $lng_profile['complied_friends'];
                    $stmt = $db->prepare("INSERT INTO `cms_mail` SET
					    `user_id` = '$user_id',
					    `from_id` = '$id',
					    `text` = ?,
					    `time` = '" . time() . "',
					    `sys` = '1',
					    `them` = ?
					");
                    $stmt->execute([
                        $text,
                        $lng_profile['friendship']
                    ]);

                    $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $id . ']' . $result['name'] . '[/url] ' . $lng_profile['offers_friends'] . ' [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=ok&id=' . $id . ']' . $lng_profile['confirm'] . '[/url] | [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=no&id=' . $id . ']' . $lng_profile['decline'] . '[/url]';
                    $stmt = $db->prepare("DELETE FROM `cms_mail` WHERE `user_id` = '$id' AND `from_id` = '$user_id' AND `text`= ?");
                    $stmt->execute([
                        $text
                    ]);
                    echo '<div class="gmenu"><p>' . $lng_profile['confirmed_friendship'] . ' ' . $result['name'] . ' ' . $lng_profile['your_friend'] . '.</p></div>';
                } else {
                    echo '<div class="gmenu"><form action="profile.php?act=friends&amp;do=ok&amp;id=' . $id . '" method="post"><div>
					' . $lng_profile['really_demand_confirm'] . '<br />
					<input type="submit" name="submit" value="' . $lng_profile['confirm'] . '"/>
					</div></form></div>';
                }
            } else {
                echo functions::display_error($lng_profile['user_already_friend']);
            }
            break;

        case 'delete':
            $fr = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND ((`from_id`='$id' AND `user_id`='$user_id') OR (`from_id`='$user_id' AND `user_id`='$id'))")->fetchColumn();

            if ($fr == 2) {
                if (isset($_POST['submit'])) {
                    $db->exec("UPDATE `cms_contact` SET
					  `type`='1',
					  `friends`='0'
					  WHERE `user_id`='$id'
					  AND `from_id`='$user_id'
					");

                    $db->exec("UPDATE `cms_contact` SET
					  `type`='1',
					  `friends`='0'
					  WHERE `user_id`='$user_id'
					  AND `from_id`='$id'
					");

                    $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $user_id . ']' . $user['name'] . '[/url] ' . $lng_profile['deleted_you_friends'];
                    $stmt = $db->prepare("INSERT INTO `cms_mail` SET
					    `user_id` = '$user_id',
					    `from_id` = '$id',
					    `text` = ?,
					    `time` = '" . time() . "',
					    `sys` = '1',
					    `them` = ?
					");
                    $stmt->execute([
                        $text,
                        $lng_profile['friendship']
                    ]);
                    echo '<div class="rmenu">' . $lng_profile['you_deleted_friends'] . '</div>';
                } else {
                    echo '<div class="gmenu"><form action="profile.php?act=friends&amp;do=delete&amp;id=' . $id . '" method="post"><div>
					' . $lng_profile['really_deleted_friends'] . '<br />
					<input type="submit" name="submit" value="' . $lng_profile['confirm'] . '"/>
					</div></form></div>';
                }
            } else {
                echo functions::display_error($lng_profile['removing_not_possible']);
            }
            break;

        case 'no':
            $fr = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND ((`from_id`='$id' AND `user_id`='$user_id') OR (`from_id`='$user_id' AND `user_id`='$id'))")->fetchColumn();

            if ($fr != 2) {
                if (isset($_POST['submit'])) {
                    $fr_out = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND `user_id`='$id' AND `from_id`='$user_id'")->fetchColumn();

                    if ($fr_out == 0) {
                        echo functions::display_error($lng_profile['not_demand_friendship']);
                        echo '<div class="bmenu"><a href="profile.php?user=' . $id . '">' . $lng['back'] . '</a></div>';
                        require_once('../incfiles/end.php');
                        exit;
                    }

                    $db->exec("UPDATE `cms_contact` SET `type`='1' WHERE `user_id`='$id' AND `from_id`='$user_id'");

                    $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $user_id . ']' . $user['name'] . '[/url] ' . $lng_profile['canceled_you_demand'];
                    $stmt = $db->prepare("INSERT INTO `cms_mail` SET
					    `user_id` = '$user_id',
					    `from_id` = '$id',
					    `text` = ?,
					    `time` = '" . time() . "',
					    `sys` = '1',
					    `them` = ?
				    ");
                    $stmt->execute([
                        $text,
                        $lng_profile['friendship']
                    ]);
                    $text = '[url=' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $id . ']' . $result['name'] . '[/url] ' . $lng_profile['offers_friends'] . ' [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=ok&id=' . $id . ']' . $lng_profile['confirm'] . '[/url] | [url=' . core::$system_set['homeurl'] . '/users/profile.php?act=friends&do=no&id=' . $id . ']' . $lng_profile['decline'] . '[/url]';
                    $stmt = $db->prepare("DELETE FROM `cms_mail` WHERE `user_id` = '$id' AND `from_id` = '$user_id' AND `text`= ?");
                    $stmt->execute([
                        $text
                    ]);

                    echo '<div class="rmenu">' . $lng_profile['canceled_demand'] . '</div>';
                } else {
                    echo '<div class="gmenu"><form action="profile.php?act=friends&amp;do=no&amp;id=' . $id . '" method="post"><div>
					' . $lng_profile['really_canceled_demand'] . '<br />
					<input type="submit" name="submit" value="' . $lng_profile['confirm'] . '"/>
					</div></form></div>';
                }
            } else {
                echo functions::display_error($lng_profile['already_your_friend']);
            }
            break;

        default:
            echo functions::display_error($lng_profile['pages_not_exist']);
    }
    echo '<div class="bmenu"><a href="profile.php?act=friends">' . $lng_profile['friends'] . '</a></div>';

} else {
    if ($user['id'] && $user['id'] != $user_id) {
        echo '<div class="phdr"><h3>' . $lng_profile['friends'] . ' ' . $user['name'] . '</h3></div>';

        if ($set_mail['cat_friends']) {
            $sort = isset($_REQUEST['sort']) && $_REQUEST['sort'] >= 0 && $_REQUEST['sort'] <= 6 ? abs(intval($_REQUEST['sort'])) : 0;
            if ($sort) {
                $sql = " AND `cms_contact`.`man`='$sort'";
                $nav = '&amp;sort=' . $sort;
            } else {
                $sql = '';
                $nav = '';
            }
        } else {
            $sql = '';
            $nav = '';
        }
        //Получаем список контактов
        $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='{$user['id']}' AND `type`='2' AND `friends`='1' AND `ban`!='1'" . $sql)->fetchColumn();

        if ($total) {
            $stmt = $db->query("SELECT `users`.* FROM `cms_contact`
			LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
			WHERE `cms_contact`.`user_id`='{$user['id']}' AND `cms_contact`.`type`='2' AND `cms_contact`.`friends`='1' AND `cms_contact`.`ban`!='1'$sql ORDER BY `cms_contact`.`time` DESC LIMIT " . $start . "," . $kmess
            );
            $i = 0;

            while ($row = $stmt->fetch()) {
                echo $i % 2 ? '<div class="list1">' : '<div class="list2">';

                if ($row['id'] == $user_id) {
                    $subtext = '<a href="../mail/index.php?act=write&amp;id=' . $user['id'] . '">' . $lng_profile['correspondence'] . '</a>';
                } else {
                    $subtext = '<a href="../mail/index.php?act=write&amp;id=' . $row['id'] . '">' . $lng['write'] . '</a> | <a href="../mail/index.php?id=' . $row['id'] . '">' . $lng_profile['add_contacts'] . '</a> | <a href="../mail/index.php?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . $lng_profile['add_ignor'] . '</a>';
                }

                $arg = array('sub' => $subtext);
                if ($row['id'] == $user_id) {
                    $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$user['id']}' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='{$user['id']}')) AND `sys`!='1' AND `delete`!='$user_id';")->fetchColumn();
                    $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`from_id`='{$user['id']}' AND `cms_mail`.`user_id`='$user_id' AND `read`='0' AND `sys`!='1' AND `delete`!='$user_id';")->fetchColumn();
                    $arg['header'] = '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')';
                }
                echo functions::display_user($row, $arg);
                echo '</div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            //Навигация
            if ($total > $kmess) {
                echo '<p>' . functions::display_pagination('profile.php?act=friends' . $nav . '&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="profile.php" method="get">
				<input type="hidden" name="act" value="friends"/>
				' . ($nav ? '<input type="hidden" name="sort" value="' . $sort . '"/>' : '') . '
				<input type="hidden" name="user" value="' . $user['id'] . '"/>
				<input type="text" name="page" size="2"/>
				<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
        } else {
            echo '<div class="rmenu">' . $lng_profile['not_friends'] . '</div>';
        }

        if ($set_mail['cat_friends']) {
            echo '<div class="menu"><form action="profile.php?act=friends&amp;user=' . $user['id'] . '" method="post"><div>
			<select name="sort">
			<option value="0">Все</option>
			<option value="1"' . ($sort == 1 ? ' selected="selected"' : '') . '>' . $lng_profile['friend'] . '</option>
			<option value="2"' . ($sort == 2 ? ' selected="selected"' : '') . '>' . $lng_profile['classfriend'] . '</option>
			<option value="3"' . ($sort == 3 ? ' selected="selected"' : '') . '>' . $lng_profile['colleague'] . '</option>
			<option value="4"' . ($sort == 4 ? ' selected="selected"' : '') . '>' . $lng_profile['best_friend'] . '</option>
			<option value="5"' . ($sort == 5 ? ' selected="selected"' : '') . '>' . $lng_profile['classmate'] . '</option>
			<option value="6"' . ($sort == 6 ? ' selected="selected"' : '') . '>' . $lng_profile['relative'] . '</option>
			</select>
			<input type="submit" value="' . $lng_profile['sea_friends'] . ' &gt;&gt;"/></div></form></div>';
        }
    } else {
        switch ($do) {
            case 'demands':
                $off = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `from_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`='0'")->fetchColumn();
                $dem = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`='0'")->fetchColumn();

                echo '<div class="phdr"><b>' . $lng_profile['friends'] . '</b></div>';
                echo '<div class="topmenu"><a href="profile.php?act=friends">' . $lng_profile['my_friends'] . '</a> | <b>' . $lng_profile['my_demand'] . '</b>' . ($dem ? ' (<span class="red">' . $dem . '</span>)' : '') . ' | <a href="profile.php?act=friends&amp;do=offers">' . $lng_profile['my_offers'] . '</a>' . ($off ? ' (<span class="red">' . $off . '</span>)' : '') . '</div>';

                $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`!='1'")->fetchColumn();

                if ($total) {
                    $stmt = $db->query("SELECT `users`.* FROM `cms_contact`
					LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
					WHERE `cms_contact`.`user_id`='" . $user_id . "' AND `cms_contact`.`type`='2' AND `cms_contact`.`friends`='0' AND `ban`!='1' ORDER BY `cms_contact`.`time` DESC LIMIT " . $start . "," . $kmess);
                    $i = 0;

                    while ($row = $stmt->fetch()) {
                        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';

                        $subtext = '<a href="../mail/index.php?act=write&amp;id=' . $row['id'] . '">' . $lng['write'] . '</a> | <a href="profile.php?act=friends&amp;do=cancel&amp;id=' . $row['id'] . '">' . $lng_profile['cancel_demand'] . '</a>';
                        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='{$row['id']}')) AND `sys`='0' AND `delete`!='$user_id';")->fetchColumn();
                        $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`from_id`='{$row['id']}' AND `cms_mail`.`user_id`='$user_id' AND `read`='0' AND `sys`='0' AND `delete`!='$user_id';")->fetchColumn();

                        $arg = array(
                            'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
                            'sub'    => $subtext
                        );
                        echo functions::display_user($row, $arg);
                        echo '</div>';
                    }
                } else {
                    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
                }
                echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
                if ($total > $kmess) {
                    echo '<p>' . functions::display_pagination('profile.php?act=friends&amp;', $start, $total, $kmess) . '</p>';
                    echo '<p><form action="profile.php" method="get">
						<input type="hidden" name="act" value="friends"/>
						<input type="hidden" name="do" value="demands"/>
						<input type="text" name="page" size="2"/>
						<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
                }
                break;

            case 'offers':
                $off = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `from_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`='0'")->fetchColumn();
                $dem = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`='0'")->fetchColumn();

                echo '<div class="phdr"><b>' . $lng_profile['friends'] . '</b></div>';
                echo '<div class="topmenu"><a href="profile.php?act=friends">' . $lng_profile['my_friends'] . '</a> | <a href="profile.php?act=friends&amp;do=demands">' . $lng_profile['my_demand'] . '</a> ' . ($dem ? '(<span class="red">' . $dem . '</span>)' : '') . '| <b>' . $lng_profile['my_offers'] . '</b>' . ($off ? ' (<span class="red">' . $off . '</span>)' : '') . '</div>';

                $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `from_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`!='1'")->fetchColumn();

                if ($total) {
                    $stmt = $db->query("SELECT `users`.* FROM `cms_contact`
					LEFT JOIN `users` ON `cms_contact`.`user_id`=`users`.`id`
					WHERE `cms_contact`.`from_id`='" . $user_id . "' AND `cms_contact`.`type`='2' AND `cms_contact`.`friends`='0' AND `ban`!='1' ORDER BY `cms_contact`.`time` DESC LIMIT " . $start . "," . $kmess);
                    $i = 0;

                    while ($row = $stmt->fetch()) {
                        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
                        $subtext = '<a href="../mail/index.php?act=write&amp;id=' . $row['id'] . '">' . $lng['write'] . '</a> | <a class="underline" href="profile.php?act=friends&amp;do=ok&amp;id=' . $row['id'] . '">' . $lng_profile['confirm_friendship'] . '</a> | <a class="underline" href="profile.php?act=friends&amp;do=no&amp;id=' . $row['id'] . '">' . $lng_profile['decline_friendship'] . '</a>';
                        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='{$row['user_id']}')) AND `delete`!='$user_id' AND `spam`='0' AND `sys`='0';")->fetchColumn();
                        $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`from_id`='{$row['id']}' AND `cms_mail`.`user_id`='$user_id' AND `read`='0' AND `delete`!='$user_id' AND `spam`='0' AND `sys`='0';")->fetchColumn();
                        $arg = array(
                            'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
                            'sub'    => $subtext
                        );
                        echo functions::display_user($row, $arg);
                        echo '</div>';
                    }
                } else {
                    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
                }
                echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
                if ($total > $kmess) {
                    echo '<p>' . functions::display_pagination('profile.php?act=friends&amp;do=offers&amp;', $start, $total, $kmess) . '</p>';
                    echo '<p><form action="profile.php" method="get">
						<input type="hidden" name="act" value="friends"/>
						<input type="hidden" name="do" value="offers"/>
						<input type="text" name="page" size="2"/>
						<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
                }
                break;

            case 'online':
                echo '<div class="phdr"><b>' . $lng_profile['friends'] . ' ' . $lng['online'] . '</b></div>';
                //Получаем список контактов
                $total = $db->query("SELECT COUNT(*) FROM `cms_contact`
				LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
				WHERE `cms_contact`.`user_id`='" . $user_id . "' AND `cms_contact`.`type`='2' AND `cms_contact`.`friends`='1' AND `cms_contact`.`ban`!='1' AND `users`.`lastdate` > " . (time() - 300) . "
				")->fetchColumn();

                if ($total) {
                    $stmt = $db->query("SELECT `users`.* FROM `cms_contact`
					LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
					WHERE `cms_contact`.`user_id`='" . $user_id . "' AND `cms_contact`.`type`='2' AND `cms_contact`.`friends`='1' AND `cms_contact`.`ban`!='1' AND `users`.`lastdate` > " . (time() - 300) . " ORDER BY `cms_contact`.`time` DESC LIMIT " . $start . "," . $kmess
                    );
                    $i = 0;

                    while ($row = $stmt->fetch()) {
                        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
                        $subtext = '<a href="../mail/index.php?act=write&amp;id=' . $row['id'] . '">' . $lng['write'] . '</a> | <a href="profile.php?act=friends&amp;do=delete&amp;id=' . $row['id'] . '">' . $lng['delete'] . '</a> | <a href="../mail/index.php?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . $lng_profile['add_ignor'] . '</a>';
                        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='{$row['id']}')) AND `sys`!='1' AND `spam`!='1' AND `delete`!='$user_id';")->fetchColumn();
                        $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='$user_id' AND `read`='0' AND `sys`!='1' AND `spam`!='1' AND `delete`!='$user_id';")->fetchColumn();
                        $arg = array(
                            'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
                            'sub'    => $subtext
                        );
                        echo functions::display_user($row, $arg);
                        echo '</div>';
                    }
                } else {
                    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
                }

                echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
                if ($total > $kmess) {
                    echo '<p>' . functions::display_pagination('profile.php?act=friends&amp;', $start, $total, $kmess) . '</p>';
                    echo '<p><form action="profile.php" method="get">
						<input type="hidden" name="act" value="friends"/>
						<input type="text" name="page" size="2"/>
						<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
                }
                break;

            default:
                $off = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `from_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`='0'")->fetchColumn();
                $dem = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `type`='2' AND `friends`='0' AND `ban`='0'")->fetchColumn();

                echo '<div class="phdr"><b>' . $lng_profile['friends'] . '</b></div>';
                echo '<div class="topmenu"><b>' . $lng_profile['my_friends'] . '</b> | <a href="profile.php?act=friends&amp;do=demands">' . $lng_profile['my_demand'] . '</a>' . ($dem ? ' (<span class="red">' . $dem . '</span>)' : '') . ' | <a href="profile.php?act=friends&amp;do=offers">' . $lng_profile['my_offers'] . '</a>' . ($off ? ' (<span class="red">' . $off . '</span>)' : '') . '</div>';

                if ($set_mail['cat_friends']) {
                    $sort = isset($_REQUEST['sort']) && $_REQUEST['sort'] >= 0 && $_REQUEST['sort'] <= 6 ? abs(intval($_REQUEST['sort'])) : 0;
                    if ($sort) {
                        $sql = " AND `cms_contact`.`man`='$sort'";
                        $nav = '&amp;sort=' . $sort;
                    } else {
                        $sql = '';
                        $nav = '';
                    }
                } else {
                    $sql = '';
                    $nav = '';
                }
                //Получаем список друзей
                $total = $db->query("SELECT COUNT(*) FROM `cms_contact`
			        LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
			        WHERE `cms_contact`.`user_id`='" . $user_id . "'
			        AND `cms_contact`.`type`='2'
			        AND `cms_contact`.`friends`='1'
			        AND `cms_contact`.`ban`!='1'$sql
			    ")->fetchColumn();
                if ($total) {
                    $stmt = $db->query("SELECT `users`.* FROM `cms_contact`
				        LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
				        WHERE `cms_contact`.`user_id`='" . $user_id . "'
				        AND `cms_contact`.`type`='2'
				        AND `cms_contact`.`friends`='1'
				        AND `cms_contact`.`ban`!='1'$sql
				        ORDER BY `cms_contact`.`time` DESC
				        LIMIT " . $start . "," . $kmess
                    );
                    $i = 0;

                    while ($row = $stmt->fetch()) {
                        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';

                        $subtext = '<a href="../mail/index.php?act=write&amp;id=' . $row['id'] . '">' . $lng['write'] . '</a> | <a href="profile.php?act=friends&amp;do=delete&amp;id=' . $row['id'] . '">' . $lng['delete'] . '</a> | <a href="../mail/index.php?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . $lng_profile['add_ignor'] . '</a>';
                        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='{$row['id']}')) AND `sys`!='1' AND `spam`!='1' AND `delete`!='$user_id';")->fetchColumn();
                        $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='$user_id' AND `read`='0' AND `sys`!='1' AND `spam`!='1' AND `delete`!='$user_id';")->fetchColumn();

                        $arg = array(
                            'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
                            'sub'    => $subtext
                        );
                        echo functions::display_user($row, $arg);
                        echo '</div>';
                    }
                } else {
                    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
                }
                echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
                if ($total > $kmess) {
                    echo '<p>' . functions::display_pagination('profile.php?act=friends' . $nav . '&amp;', $start, $total, $kmess) . '</p>' .
                        '<p><form action="profile.php" method="get">' .
                        '<input type="hidden" name="act" value="friends"/>' .
                        ($nav ? '<input type="hidden" name="sort" value="' . $sort . '"/>' : '') .
                        '<input type="text" name="page" size="2"/>' .
                        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
                }
                if ($set_mail['cat_friends']) {
                    echo '<div class="menu"><form action="profile.php?act=friends" method="post"><div>' .
                        '<select name="sort">' .
                        '<option value="0">Все</option>' .
                        '<option value="1"' . ($sort == 1 ? ' selected="selected"' : '') . '>' . $lng_profile['friend'] . '</option>' .
                        '<option value="2"' . ($sort == 2 ? ' selected="selected"' : '') . '>' . $lng_profile['classfriend'] . '</option>' .
                        '<option value="3"' . ($sort == 3 ? ' selected="selected"' : '') . '>' . $lng_profile['colleague'] . '</option>' .
                        '<option value="4"' . ($sort == 4 ? ' selected="selected"' : '') . '>' . $lng_profile['best_friend'] . '</option>' .
                        '<option value="5"' . ($sort == 5 ? ' selected="selected"' : '') . '>' . $lng_profile['classmate'] . '</option>' .
                        '<option value="6"' . ($sort == 6 ? ' selected="selected"' : '') . '>' . $lng_profile['relative'] . '</option>' .
                        '</select>' .
                        '<input type="submit" value="' . $lng_profile['sea_friends'] . ' &gt;&gt;"/></div></form></div>';
                }
        }
        echo '<p><a href="profile.php?act=office">' . $lng['personal'] . '</a></p>';
    }
}