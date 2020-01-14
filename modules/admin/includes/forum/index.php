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

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */


// Панель управления форумом
$total_cat = $db->query('SELECT COUNT(*) FROM `forum_sections` WHERE `section_type` != 1 OR `section_type` IS NULL')->fetchColumn();
$total_sub = $db->query('SELECT COUNT(*) FROM `forum_sections` WHERE `section_type` = 1')->fetchColumn();
$total_thm = $db->query('SELECT COUNT(*) FROM `forum_topic`')->fetchColumn();
$total_thm_del = $db->query('SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` = 1')->fetchColumn();
$total_msg = $db->query('SELECT COUNT(*) FROM `forum_messages`')->fetchColumn();
$total_msg_del = $db->query('SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` = 1')->fetchColumn();
$total_files = $db->query('SELECT COUNT(*) FROM `cms_forum_files`')->fetchColumn();
$total_votes = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '1'")->fetchColumn();

echo '<div class="phdr"><a href="./"><b>' . __('Admin Panel') . '</b></a> | ' . __('Forum Management') . '</div>' .
    '<div class="gmenu"><p><h3>' . __('Statistic') . '</h3><ul>' .
    '<li>' . __('Categories') . ':&#160;' . $total_cat . '</li>' .
    '<li>' . __('Sections') . ':&#160;' . $total_sub . '</li>' .
    '<li>' . __('Topics') . ':&#160;' . $total_thm . '&#160;/&#160;<span class="red">' . $total_thm_del . '</span></li>' .
    '<li>' . __('Messages') . ':&#160;' . $total_msg . '&#160;/&#160;<span class="red">' . $total_msg_del . '</span></li>' .
    '<li>' . __('Files') . ':&#160;' . $total_files . '</li>' .
    '<li>' . __('Votes') . ':&#160;' . $total_votes . '</li>' .
    '</ul></p></div>' .
    '<div class="menu"><p><h3>' . __('Settings') . '</h3><ul>' .
    '<li><a href="?act=forum&amp;mod=cat"><b>' . __('Forum structure') . '</b></a></li>' .
    '<li><a href="?act=forum&amp;mod=hposts">' . __('Hidden posts') . '</a> (' . $total_msg_del . ')</li>' .
    '<li><a href="?act=forum&amp;mod=htopics">' . __('Hidden topics') . '</a> (' . $total_thm_del . ')</li>' .
    '</ul></p></div>' .
    '<div class="phdr"><a href="../forum/">' . __('Go to Forum') . '</a></div>';
